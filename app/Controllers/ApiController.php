<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;
use App\Models\ApiUsageLogModel;
use App\Models\FeaturedWinnerModel;

class ApiController extends BaseController
{
    private function decodePermissions(?string $permissions): array
    {
        if (!$permissions) {
            return [];
        }

        $decoded = json_decode($permissions, true);
        return is_array($decoded) ? array_values(array_unique($decoded)) : [];
    }

    private function authenticateApiKey(?string $requiredScope = null)
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Missing bearer token']);
        }

        $token = trim(substr($authHeader, 7));
        $apiKeyModel = new ApiKeyModel();
        $apiKey = $apiKeyModel->where('api_key', $token)->where('is_active', 1)->first();

        if (!$apiKey) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Invalid bearer token']);
        }

        if ($requiredScope !== null) {
            $permissions = $this->decodePermissions($apiKey['permissions'] ?? null);
            if (!in_array($requiredScope, $permissions, true)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'message' => 'Missing required scope: ' . $requiredScope,
                ]);
            }
        }

        return $apiKey;
    }

    private function logUsage(int $apiKeyId, string $endpoint): void
    {
        (new ApiUsageLogModel())->insert([
            'api_key_id' => $apiKeyId,
            'endpoint' => $endpoint,
            'method' => strtoupper($this->request->getMethod()),
            'client_ip' => (string) $this->request->getIPAddress(),
            'used_at' => date('Y-m-d H:i:s'),
        ]);

        (new ApiKeyModel())->update($apiKeyId, ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public function alumni()
    {
        $apiKey = $this->authenticateApiKey('read:alumni');
        if (!is_array($apiKey)) {
            return $apiKey;
        }

        $db = \Config\Database::connect();
        $search = trim((string) $this->request->getGet('search'));
        $programme = trim((string) $this->request->getGet('programme'));
        $industrySector = trim((string) $this->request->getGet('industry_sector'));
        $graduationYear = trim((string) $this->request->getGet('graduation_year'));

        $builder = $db->table('users u')
            ->select('u.id, u.name, u.email, u.linkedin_url, u.job_title_now, u.programme, u.graduation_year')
            ->select('MAX(d.completion_date) AS latest_completion_date')
            ->select('GROUP_CONCAT(DISTINCT eh.industry_sector ORDER BY eh.industry_sector SEPARATOR ", ") AS industry_sector')
            ->join('degrees d', 'd.user_id = u.id', 'left')
            ->join('employment_history eh', 'eh.user_id = u.id', 'left')
            ->groupBy('u.id')
            ->orderBy('u.name', 'ASC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('u.name', $search)
                ->orLike('u.email', $search)
                ->orLike('u.job_title_now', $search)
                ->groupEnd();
        }

        if ($programme !== '') {
            $builder->like('u.programme', $programme);
        }

        if ($industrySector !== '') {
            $builder->where('eh.industry_sector', $industrySector);
        }

        if ($graduationYear !== '') {
            $escapedYear = $db->escape($graduationYear);
            $builder->groupStart()
                ->where('u.graduation_year', $graduationYear)
                ->orWhere("YEAR(d.completion_date) = {$escapedYear}", null, false)
                ->groupEnd();
        }

        $rows = $builder->get()->getResultArray();

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'linkedin_url' => (string) ($row['linkedin_url'] ?? ''),
                'job_title_now' => (string) ($row['job_title_now'] ?? ''),
                'programme' => (string) ($row['programme'] ?? ''),
                'industry_sector' => (string) ($row['industry_sector'] ?? ''),
                'graduation_year' => (string) ($row['graduation_year'] ?? ''),
                'degree_completion_date' => (string) ($row['latest_completion_date'] ?? ''),
            ];
        }, $rows);

        $this->logUsage((int) $apiKey['id'], '/api/alumni');

        return $this->response->setJSON([
            'status' => 'success',
            'filters' => [
                'search' => $search,
                'programme' => $programme,
                'industry_sector' => $industrySector,
                'graduation_year' => $graduationYear,
            ],
            'data' => $data,
        ]);
    }

    public function featuredToday()
    {
        $apiKey = $this->authenticateApiKey('read:alumni_of_day');
        if (!is_array($apiKey)) {
            return $apiKey;
        }

        $winner = (new FeaturedWinnerModel())
            ->select('featured_winners.feature_date, featured_winners.winning_bid, users.name, users.bio, users.linkedin_url, users.job_title_now, users.profile_image')
            ->join('users', 'users.id = featured_winners.user_id', 'left')
            ->where('feature_date', date('Y-m-d'))
            ->first();

        $this->logUsage((int) $apiKey['id'], '/api/featured');

        if (! $winner) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'No featured alumnus selected for today']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $winner,
        ]);
    }
}
