<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class AnalyticsApiController extends BaseController
{
    private function deny(int $code, string $message)
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status' => 'error',
            'message' => $message,
        ]);
    }

    private function decodePermissions(?string $permissions): array
    {
        if (!$permissions) {
            return [];
        }

        $decoded = json_decode($permissions, true);
        return is_array($decoded) ? array_values(array_unique($decoded)) : [];
    }

    private function logApiUsage(int $apiKeyId, string $endpoint): void
    {
        $db = Database::connect();

        if (!$db->tableExists('api_usage_logs')) {
            return;
        }

        $db->table('api_usage_logs')->insert([
            'api_key_id' => $apiKeyId,
            'endpoint' => $endpoint,
            'method' => strtoupper($this->request->getMethod()),
            'client_ip' => (string) $this->request->getIPAddress(),
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function authorizeAnalyticsAccess()
    {
        if (session()->get('user_id')) {
            return true;
        }

        $auth = $this->request->getHeaderLine('Authorization');
        if (!$auth || strpos($auth, 'Bearer ') !== 0) {
            return $this->deny(401, 'Missing bearer token');
        }

        $token = trim(substr($auth, 7));
        $db = Database::connect();

        $key = $db->table('api_keys')
            ->where('api_key', $token)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if (!$key) {
            return $this->deny(401, 'Invalid token');
        }

        $permissions = $this->decodePermissions($key['permissions'] ?? null);
        if (!in_array('read:analytics', $permissions, true)) {
            return $this->deny(403, 'Missing required scope: read:analytics');
        }

        $this->logApiUsage((int) $key['id'], $this->request->getUri()->getPath());
        $db->table('api_keys')
            ->where('id', $key['id'])
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    private function hasColumn($db, string $table, string $column): bool
    {
        return $db->tableExists($table) && in_array($column, $db->getFieldNames($table), true);
    }

    private function getFilters(): array
    {
        return [
            'programme' => trim((string) $this->request->getGet('programme')),
            'graduation_year' => trim((string) $this->request->getGet('graduation_year')),
            'graduation_date' => trim((string) $this->request->getGet('graduation_date')),
            'industry_sector' => trim((string) $this->request->getGet('industry_sector')),
        ];
    }

    private function getFilteredUserIds(): ?array
    {
        $filters = $this->getFilters();
        $activeFilters = array_filter($filters, static fn ($value) => $value !== '');

        if (empty($activeFilters)) {
            return null;
        }

        $db = Database::connect();
        if (!$db->tableExists('users')) {
            return [];
        }

        $builder = $db->table('users u')->select('u.id')->groupBy('u.id');
        $userFields = $db->getFieldNames('users');
        $employmentExists = $db->tableExists('employment_history');
        $degreeExists = $db->tableExists('degrees');
        $escapedGraduationYear = $db->escape($filters['graduation_year']);
        $escapedGraduationDate = $db->escape($filters['graduation_date']);

        if ($filters['programme'] !== '') {
            $hasProgrammeSource = false;

            if (in_array('programme', $userFields, true)) {
                if ($degreeExists && $this->hasColumn($db, 'degrees', 'degree_name')) {
                    $builder->join('degrees d_prog', 'd_prog.user_id = u.id', 'left');
                }

                $builder->groupStart()
                    ->like('u.programme', $filters['programme']);
                $hasProgrammeSource = true;

                if ($degreeExists && $this->hasColumn($db, 'degrees', 'degree_name')) {
                    $builder->orLike('d_prog.degree_name', $filters['programme']);
                }

                $builder->groupEnd();
            } elseif ($degreeExists && $this->hasColumn($db, 'degrees', 'degree_name')) {
                $builder->join('degrees d_prog', 'd_prog.user_id = u.id', 'left');
                $builder->like('d_prog.degree_name', $filters['programme']);
                $hasProgrammeSource = true;
            }

            if (!$hasProgrammeSource) {
                return [];
            }
        }

        if ($filters['industry_sector'] !== '') {
            if (!$employmentExists || !$this->hasColumn($db, 'employment_history', 'industry_sector')) {
                return [];
            }

            $builder->join('employment_history eh_filter', 'eh_filter.user_id = u.id', 'left');
            $builder->where('eh_filter.industry_sector', $filters['industry_sector']);
        }

        if ($filters['graduation_year'] !== '' || $filters['graduation_date'] !== '') {
            $graduationApplied = false;
            $hasDegreeCompletionDates = $degreeExists && $this->hasColumn($db, 'degrees', 'completion_date');

            if ($hasDegreeCompletionDates) {
                $builder->join('degrees d_grad', 'd_grad.user_id = u.id', 'left');
            }

            if ($filters['graduation_year'] !== '' && in_array('graduation_year', $userFields, true)) {
                $builder->groupStart()->where('u.graduation_year', $filters['graduation_year']);

                if ($hasDegreeCompletionDates) {
                    $builder->orWhere("YEAR(d_grad.completion_date) = {$escapedGraduationYear}", null, false);
                }

                if (in_array('graduation_date', $userFields, true)) {
                    $builder->orWhere("YEAR(u.graduation_date) = {$escapedGraduationYear}", null, false);
                }

                $builder->groupEnd();
                $graduationApplied = true;
            } elseif ($filters['graduation_year'] !== '' && in_array('graduation_date', $userFields, true)) {
                $builder->groupStart()->where("YEAR(u.graduation_date) = {$escapedGraduationYear}", null, false);

                if ($hasDegreeCompletionDates) {
                    $builder->orWhere("YEAR(d_grad.completion_date) = {$escapedGraduationYear}", null, false);
                }

                $builder->groupEnd();
                $graduationApplied = true;
            } elseif ($filters['graduation_year'] !== '' && $hasDegreeCompletionDates) {
                $builder->where("YEAR(d_grad.completion_date) = {$escapedGraduationYear}", null, false);
                $graduationApplied = true;
            }

            if ($filters['graduation_date'] !== '') {
                if (in_array('graduation_date', $userFields, true)) {
                    $builder->groupStart()->where('u.graduation_date', $filters['graduation_date']);

                    if ($hasDegreeCompletionDates) {
                        $builder->orWhere("d_grad.completion_date = {$escapedGraduationDate}", null, false);
                    }

                    $builder->groupEnd();
                    $graduationApplied = true;
                } elseif ($hasDegreeCompletionDates) {
                    $builder->where("d_grad.completion_date = {$escapedGraduationDate}", null, false);
                    $graduationApplied = true;
                }
            }

            if (!$graduationApplied && !$hasDegreeCompletionDates) {
                return [];
            }
        }

        $rows = $builder->get()->getResultArray();
        return array_values(array_map(static fn ($row) => (int) $row['id'], $rows));
    }

    private function applyUserFilterToBuilder($builder, string $column): void
    {
        $userIds = $this->getFilteredUserIds();

        if ($userIds === null) {
            return;
        }

        if (empty($userIds)) {
            $builder->where('1 = 0');
            return;
        }

        $builder->whereIn($column, $userIds);
    }

    private function summaryPayload(): array
    {
        $db = Database::connect();
        $userIds = $this->getFilteredUserIds();

        $payload = [
            'total_alumni' => 0,
            'total_certifications' => 0,
            'total_employment_records' => 0,
        ];

        if ($db->tableExists('users')) {
            $builder = $db->table('users');
            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('id', $userIds);
                }
            }
            $payload['total_alumni'] = $builder->countAllResults();
        }

        if ($db->tableExists('certifications')) {
            $builder = $db->table('certifications');
            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('user_id', $userIds);
                }
            }
            $payload['total_certifications'] = $builder->countAllResults();
        }

        if ($db->tableExists('employment_history')) {
            $builder = $db->table('employment_history');
            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('user_id', $userIds);
                }
            }
            $payload['total_employment_records'] = $builder->countAllResults();
        }

        return $payload;
    }

    public function summary()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'filters' => $this->getFilters(),
            'data' => $this->summaryPayload(),
        ]);
    }

    public function industries()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];

        if ($this->hasColumn($db, 'employment_history', 'industry_sector')) {
            $builder = $db->table('employment_history')
                ->select('industry_sector, COUNT(*) AS total')
                ->where('industry_sector IS NOT NULL')
                ->where("industry_sector != ''", null, false)
                ->groupBy('industry_sector')
                ->orderBy('total', 'DESC');

            $this->applyUserFilterToBuilder($builder, 'user_id');
            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function topEmployers()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];

        if ($this->hasColumn($db, 'employment_history', 'company_name')) {
            $builder = $db->table('employment_history')
                ->select('company_name, COUNT(*) AS total')
                ->where('company_name IS NOT NULL')
                ->where("company_name != ''", null, false)
                ->groupBy('company_name')
                ->orderBy('total', 'DESC')
                ->limit(10);

            $this->applyUserFilterToBuilder($builder, 'user_id');
            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function jobTitles()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];

        if ($this->hasColumn($db, 'employment_history', 'job_title')) {
            $builder = $db->table('employment_history')
                ->select('job_title, COUNT(*) AS total')
                ->where('job_title IS NOT NULL')
                ->where("job_title != ''", null, false)
                ->groupBy('job_title')
                ->orderBy('total', 'DESC')
                ->limit(10);

            $this->applyUserFilterToBuilder($builder, 'user_id');
            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function programmes()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];
        $userIds = $this->getFilteredUserIds();

        if ($this->hasColumn($db, 'users', 'programme')) {
            $builder = $db->table('users')
                ->select('programme, COUNT(*) AS total')
                ->where('programme IS NOT NULL')
                ->where("programme != ''", null, false)
                ->groupBy('programme')
                ->orderBy('total', 'DESC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        }

        if (empty($data) && $this->hasColumn($db, 'degrees', 'degree_name')) {
            $builder = $db->table('degrees')
                ->select('degree_name AS programme, COUNT(*) AS total')
                ->where('degree_name IS NOT NULL')
                ->where("degree_name != ''", null, false)
                ->groupBy('degree_name')
                ->orderBy('total', 'DESC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('user_id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function graduationYears()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];
        $userIds = $this->getFilteredUserIds();

        if ($this->hasColumn($db, 'users', 'graduation_year')) {
            $builder = $db->table('users')
                ->select('graduation_year, COUNT(*) AS total')
                ->where('graduation_year IS NOT NULL')
                ->groupBy('graduation_year')
                ->orderBy('graduation_year', 'ASC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        }

        if (empty($data) && $this->hasColumn($db, 'users', 'graduation_date')) {
            $builder = $db->table('users')
                ->select('YEAR(graduation_date) AS graduation_year, COUNT(*) AS total')
                ->where('graduation_date IS NOT NULL')
                ->groupBy('YEAR(graduation_date)')
                ->orderBy('graduation_year', 'ASC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        }

        if (empty($data) && $this->hasColumn($db, 'degrees', 'completion_date')) {
            $builder = $db->table('degrees')
                ->select('YEAR(completion_date) AS graduation_year, COUNT(*) AS total')
                ->where('completion_date IS NOT NULL')
                ->groupBy('YEAR(completion_date)')
                ->orderBy('graduation_year', 'ASC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('user_id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function certifications()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];

        if ($this->hasColumn($db, 'certifications', 'certification_name')) {
            $builder = $db->table('certifications')
                ->select('certification_name, COUNT(*) AS total')
                ->where('certification_name IS NOT NULL')
                ->where("certification_name != ''", null, false)
                ->groupBy('certification_name')
                ->orderBy('total', 'DESC')
                ->limit(10);

            $this->applyUserFilterToBuilder($builder, 'user_id');
            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function skillsGap()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];

        if ($this->hasColumn($db, 'certifications', 'certification_name')) {
            $builder = $db->table('certifications')
                ->select('certification_name AS skill_name, COUNT(*) AS total')
                ->where('certification_name IS NOT NULL')
                ->where("certification_name != ''", null, false)
                ->groupBy('certification_name')
                ->orderBy('total', 'DESC')
                ->limit(8);

            $this->applyUserFilterToBuilder($builder, 'user_id');
            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }

    public function geographicDistribution()
    {
        $auth = $this->authorizeAnalyticsAccess();
        if ($auth instanceof ResponseInterface) {
            return $auth;
        }

        $db = Database::connect();
        $data = [];
        $userIds = $this->getFilteredUserIds();

        if ($this->hasColumn($db, 'employment_history', 'country')) {
            $builder = $db->table('employment_history')
                ->select('country AS location_name, COUNT(*) AS total')
                ->where('country IS NOT NULL')
                ->where("country != ''", null, false)
                ->groupBy('country')
                ->orderBy('total', 'DESC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('user_id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        } elseif ($this->hasColumn($db, 'employment_history', 'location')) {
            $builder = $db->table('employment_history')
                ->select('location AS location_name, COUNT(*) AS total')
                ->where('location IS NOT NULL')
                ->where("location != ''", null, false)
                ->groupBy('location')
                ->orderBy('total', 'DESC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('user_id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        } elseif ($this->hasColumn($db, 'users', 'country')) {
            $builder = $db->table('users')
                ->select('country AS location_name, COUNT(*) AS total')
                ->where('country IS NOT NULL')
                ->where("country != ''", null, false)
                ->groupBy('country')
                ->orderBy('total', 'DESC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        } elseif ($this->hasColumn($db, 'users', 'location')) {
            $builder = $db->table('users')
                ->select('location AS location_name, COUNT(*) AS total')
                ->where('location IS NOT NULL')
                ->where("location != ''", null, false)
                ->groupBy('location')
                ->orderBy('total', 'DESC');

            if ($userIds !== null) {
                if (empty($userIds)) {
                    $builder->where('1 = 0');
                } else {
                    $builder->whereIn('id', $userIds);
                }
            }

            $data = $builder->get()->getResultArray();
        }

        return $this->response->setJSON(['status' => 'success', 'filters' => $this->getFilters(), 'data' => $data]);
    }
}
