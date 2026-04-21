<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;
use App\Models\ApiUsageLogModel;

class DeveloperController extends BaseController
{
    private const AVAILABLE_SCOPES = [
        'read:alumni' => 'Read alumni directory API data',
        'read:analytics' => 'Read analytics API endpoints',
        'read:alumni_of_day' => 'Read featured alumnus of the day endpoint',
    ];

    private function decodePermissions(?string $permissions): array
    {
        if (!$permissions) {
            return [];
        }

        $decoded = json_decode($permissions, true);
        return is_array($decoded) ? array_values(array_unique($decoded)) : [];
    }

    private function usageSummary(array $logs, array $keysById): array
    {
        $summary = [];

        foreach ($logs as $log) {
            $apiKeyId = (int) ($log['api_key_id'] ?? 0);
            $endpoint = (string) ($log['endpoint'] ?? '');
            $bucket = $apiKeyId . '|' . $endpoint;

            if (!isset($summary[$bucket])) {
                $summary[$bucket] = [
                    'key_name' => $keysById[$apiKeyId]['key_name'] ?? 'Unknown key',
                    'endpoint' => $endpoint,
                    'total_requests' => 0,
                    'last_used_at' => '',
                ];
            }

            $summary[$bucket]['total_requests']++;
            $usedAt = (string) ($log['used_at'] ?? '');
            if ($usedAt > $summary[$bucket]['last_used_at']) {
                $summary[$bucket]['last_used_at'] = $usedAt;
            }
        }

        usort($summary, static function (array $left, array $right): int {
            return strcmp($right['last_used_at'], $left['last_used_at']);
        });

        return array_slice($summary, 0, 20);
    }

    public function index()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId = (int) session()->get('user_id');
        $apiKeyModel = new ApiKeyModel();
        $logModel = new ApiUsageLogModel();

        $keys = $apiKeyModel
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        foreach ($keys as &$key) {
            $key['decoded_permissions'] = $this->decodePermissions($key['permissions'] ?? null);
        }
        unset($key);

        $keyIds = array_column($keys, 'id');
        $logs = [];
        if (!empty($keyIds)) {
            $logs = $logModel
                ->whereIn('api_key_id', $keyIds)
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->findAll();
        }

        $keysById = [];
        foreach ($keys as $key) {
            $keysById[(int) $key['id']] = $key;
        }

        foreach ($logs as &$log) {
            $apiKeyId = (int) ($log['api_key_id'] ?? 0);
            $log['key_name'] = $keysById[$apiKeyId]['key_name'] ?? 'Unknown key';
        }
        unset($log);

        return view('dev/index', [
            'keys' => $keys,
            'logs' => $logs,
            'usageSummary' => $this->usageSummary($logs, $keysById),
            'availableScopes' => self::AVAILABLE_SCOPES,
        ]);
    }

    public function generateKey()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId = (int) session()->get('user_id');
        $keyName = trim((string) $this->request->getPost('key_name'));
        $requestedPermissions = $this->request->getPost('permissions');

        if ($keyName === '') {
            return redirect()->back()->with('error', 'A key name is required.');
        }

        if (!is_array($requestedPermissions)) {
            $requestedPermissions = [];
        }

        $permissions = array_values(array_intersect(array_keys(self::AVAILABLE_SCOPES), $requestedPermissions));
        if (empty($permissions)) {
            return redirect()->back()->with('error', 'Select at least one API permission scope.');
        }

        $apiKeyModel = new ApiKeyModel();
        $activeCount = $apiKeyModel->where('user_id', $userId)->where('is_active', 1)->countAllResults();
        if ($activeCount >= 10) {
            return redirect()->back()->with('error', 'You have reached the maximum of 10 active API keys. Please revoke an existing key first.');
        }

        $rawKey = bin2hex(random_bytes(32));

        $apiKeyModel->insert([
            'user_id' => $userId,
            'key_name' => $keyName,
            'api_key' => $rawKey,
            'permissions' => json_encode($permissions),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('developer'))->with('generated_api_key', $rawKey);
    }

    public function revoke($id)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId = (int) session()->get('user_id');
        $apiKeyModel = new ApiKeyModel();
        $key = $apiKeyModel->find((int) $id);

        if (!$key || (int) $key['user_id'] !== $userId) {
            return redirect()->to(base_url('developer'))->with('error', 'API key not found or access denied.');
        }

        $apiKeyModel->update((int) $id, ['is_active' => 0]);

        return redirect()->to(base_url('developer'))->with('success', 'API key "' . esc($key['key_name']) . '" has been revoked.');
    }

    public function openApiJson()
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Alumni Influencer API',
                'version' => '1.1.0',
                'description' => 'API documentation for the Alumni Influencer coursework project. Analytics and featured alumnus endpoints require a Bearer token generated in the developer portal with the correct scopes.',
            ],
            'servers' => [
                ['url' => base_url(), 'description' => 'Local development server'],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Paste an API key generated from the developer portal.',
                    ],
                ],
                'schemas' => [
                    'ErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'error'],
                            'message' => ['type' => 'string', 'example' => 'Missing required scope: read:analytics'],
                        ],
                    ],
                    'FeaturedAlumnus' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'example' => 'Jane Smith'],
                            'bio' => ['type' => 'string', 'example' => 'Software engineer with 10 years experience.'],
                            'linkedin_url' => ['type' => 'string', 'format' => 'uri', 'example' => 'https://linkedin.com/in/janesmith'],
                            'job_title_now' => ['type' => 'string', 'example' => 'Senior Software Engineer'],
                            'profile_image' => ['type' => 'string', 'example' => 'jane.jpg'],
                            'feature_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-04-20'],
                            'winning_bid' => ['type' => 'number', 'format' => 'float', 'example' => 250.00],
                        ],
                    ],
                    'AlumniListItem' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 16],
                            'name' => ['type' => 'string', 'example' => 'Jane Smith'],
                            'email' => ['type' => 'string', 'example' => 'jane@iit.ac.lk'],
                            'linkedin_url' => ['type' => 'string', 'format' => 'uri', 'example' => 'https://linkedin.com/in/janesmith'],
                            'job_title_now' => ['type' => 'string', 'example' => 'Senior Software Engineer'],
                            'programme' => ['type' => 'string', 'example' => 'BSc Computer Science'],
                            'industry_sector' => ['type' => 'string', 'example' => 'Information Technology (IT)'],
                            'graduation_year' => ['type' => 'string', 'example' => '2026'],
                            'degree_completion_date' => ['type' => 'string', 'example' => '2026-07-01'],
                        ],
                    ],
                    'ApiKeyCreateRequest' => [
                        'type' => 'object',
                        'required' => ['key_name', 'permissions'],
                        'properties' => [
                            'key_name' => ['type' => 'string', 'example' => 'Analytics Dashboard Key'],
                            'permissions' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'enum' => array_keys(self::AVAILABLE_SCOPES),
                                ],
                                'example' => ['read:analytics', 'read:alumni_of_day'],
                            ],
                        ],
                    ],
                    'AnalyticsEnvelope' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'success'],
                            'filters' => [
                                'type' => 'object',
                                'properties' => [
                                    'programme' => ['type' => 'string', 'example' => 'BSc Computer Science'],
                                    'graduation_year' => ['type' => 'string', 'example' => '2026'],
                                    'graduation_date' => ['type' => 'string', 'example' => '2026-07-01'],
                                    'industry_sector' => ['type' => 'string', 'example' => 'Information Technology (IT)'],
                                ],
                            ],
                            'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/developer/generate-key' => [
                    'post' => [
                        'tags' => ['Developer'],
                        'summary' => 'Generate a new API key with scopes',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => ['$ref' => '#/components/schemas/ApiKeyCreateRequest'],
                                    'example' => [
                                        'key_name' => 'Analytics Key',
                                        'permissions' => ['read:alumni', 'read:analytics', 'read:alumni_of_day'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '302' => ['description' => 'Redirect back to developer dashboard with the raw key in flash data'],
                        ],
                    ],
                ],
                '/developer/revoke/{id}' => [
                    'get' => [
                        'tags' => ['Developer'],
                        'summary' => 'Revoke an API key',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '302' => ['description' => 'Redirect back to developer dashboard'],
                        ],
                    ],
                ],
                '/api/alumni' => [
                    'get' => [
                        'tags' => ['Public API'],
                        'summary' => 'List and search alumni',
                        'description' => 'Requires the read:alumni scope.',
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'search', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => 'Jane'],
                            ['name' => 'programme', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => 'BSc Computer Science'],
                            ['name' => 'industry_sector', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => 'Information Technology (IT)'],
                            ['name' => 'graduation_year', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => '2026'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Alumni list returned successfully',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'status' => 'success',
                                            'filters' => ['search' => 'Jane', 'programme' => '', 'industry_sector' => '', 'graduation_year' => '2026'],
                                            'data' => [[
                                                'id' => 16,
                                                'name' => 'Jane Smith',
                                                'email' => 'jane@iit.ac.lk',
                                                'programme' => 'BSc Computer Science',
                                                'industry_sector' => 'Information Technology (IT)',
                                                'graduation_year' => '2026',
                                                'degree_completion_date' => '2026-07-01',
                                            ]],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Missing or invalid Bearer token',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                            '403' => [
                                'description' => 'Missing read:alumni scope',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                        ],
                    ],
                ],
                '/api/featured' => [
                    'get' => [
                        'tags' => ['Public API'],
                        'summary' => "Get today's featured alumnus",
                        'description' => 'Requires the read:alumni_of_day scope.',
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'Featured alumnus returned successfully',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'status' => 'success',
                                            'data' => [
                                                'name' => 'Jane Smith',
                                                'feature_date' => '2026-04-20',
                                                'winning_bid' => 250.00,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Missing or invalid Bearer token',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                            '403' => [
                                'description' => 'API key does not have the required scope',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                        ],
                    ],
                ],
                '/api/analytics/summary' => [
                    'get' => [
                        'tags' => ['Analytics'],
                        'summary' => 'Get analytics summary counts',
                        'description' => 'Requires the read:analytics scope.',
                        'security' => [['BearerAuth' => []]],
                        'parameters' => $this->analyticsQueryParameters(),
                        'responses' => [
                            '200' => [
                                'description' => 'Summary returned successfully',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'status' => 'success',
                                            'filters' => ['programme' => 'BSc Computer Science', 'graduation_year' => '2026', 'graduation_date' => '', 'industry_sector' => 'Information Technology (IT)'],
                                            'data' => ['total_alumni' => 32, 'total_certifications' => 18, 'total_employment_records' => 24],
                                        ],
                                    ],
                                ],
                            ],
                            '403' => [
                                'description' => 'Missing read:analytics scope',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                        ],
                    ],
                ],
                '/api/analytics/industries' => $this->analyticsPathDefinition('Get industry distribution', [
                    ['industry_sector' => 'Information Technology (IT)', 'total' => 10],
                    ['industry_sector' => 'Healthcare', 'total' => 4],
                ]),
                '/api/analytics/employers' => $this->analyticsPathDefinition('Get top employers', [
                    ['company_name' => 'Tech Corp', 'total' => 6],
                    ['company_name' => 'Build Lanka', 'total' => 3],
                ]),
                '/api/analytics/job-titles' => $this->analyticsPathDefinition('Get top job titles', [
                    ['job_title' => 'Software Engineer', 'total' => 7],
                    ['job_title' => 'Project Manager', 'total' => 3],
                ]),
                '/api/analytics/programmes' => $this->analyticsPathDefinition('Get programme distribution', [
                    ['programme' => 'BSc Computer Science', 'total' => 15],
                    ['programme' => 'MBA', 'total' => 5],
                ]),
                '/api/analytics/graduation-years' => $this->analyticsPathDefinition('Get graduation year distribution', [
                    ['graduation_year' => '2024', 'total' => 8],
                    ['graduation_year' => '2025', 'total' => 11],
                ]),
                '/api/analytics/certifications' => $this->analyticsPathDefinition('Get top certifications', [
                    ['certification_name' => 'AWS Certified Cloud Practitioner', 'total' => 4],
                    ['certification_name' => 'Google Data Analytics', 'total' => 3],
                ]),
                '/api/analytics/skills-gap' => $this->analyticsPathDefinition('Get skills-gap signals', [
                    ['skill_name' => 'Cloud Computing', 'total' => 4],
                    ['skill_name' => 'Data Analysis', 'total' => 3],
                ]),
                '/api/analytics/geographic-distribution' => $this->analyticsPathDefinition('Get geographic distribution', [
                    ['location_name' => 'Sri Lanka', 'total' => 12],
                    ['location_name' => 'United Kingdom', 'total' => 5],
                ]),
            ],
        ];

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function analyticsQueryParameters(): array
    {
        return [
            ['name' => 'programme', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => 'BSc Computer Science'],
            ['name' => 'graduation_year', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => '2026'],
            ['name' => 'graduation_date', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date'], 'example' => '2026-07-01'],
            ['name' => 'industry_sector', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => 'Information Technology (IT)'],
        ];
    }

    private function analyticsPathDefinition(string $summary, array $exampleRows): array
    {
        return [
            'get' => [
                'tags' => ['Analytics'],
                'summary' => $summary,
                'description' => 'Requires the read:analytics scope.',
                'security' => [['BearerAuth' => []]],
                'parameters' => $this->analyticsQueryParameters(),
                'responses' => [
                    '200' => [
                        'description' => 'Analytics data returned successfully',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'status' => 'success',
                                    'filters' => ['programme' => '', 'graduation_year' => '2026', 'graduation_date' => '', 'industry_sector' => ''],
                                    'data' => $exampleRows,
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Missing or invalid Bearer token',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                    ],
                    '403' => [
                        'description' => 'Missing read:analytics scope',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                    ],
                ],
            ],
        ];
    }

    public function profile($id = null)
    {
        $db = \Config\Database::connect();
        $userId = $id ?? session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $certifications = $db->table('certifications')->where('user_id', $userId)->get()->getResultArray();
        $degrees = $db->table('degrees')->where('user_id', $userId)->get()->getResultArray();

        return view('developer/profile', [
            'user' => $user,
            'certifications' => $certifications,
            'qualifications' => $degrees,
        ]);
    }
}
