<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;
use App\Models\ApiUsageLogModel;

class DeveloperController extends BaseController
{
    // -------------------------------------------------------------------------
    // Developer dashboard – manage API keys and view usage statistics
    // -------------------------------------------------------------------------

    public function index()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId      = (int) session()->get('user_id');
        $apiKeyModel = new ApiKeyModel();
        $logModel    = new ApiUsageLogModel();

        $keys = $apiKeyModel->where('user_id', $userId)->orderBy('id', 'DESC')->findAll();

        // Fetch recent usage logs for all keys belonging to this user
        $keyIds = array_column($keys, 'id');
        $logs   = [];
        if (!empty($keyIds)) {
            $logs = $logModel
                ->whereIn('api_key_id', $keyIds)
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->findAll();
        }

        return view('dev/index', [
            'keys' => $keys,
            'logs' => $logs,
        ]);
    }

    // -------------------------------------------------------------------------
    // Generate a new API key (cryptographically random, 64-char hex)
    // -------------------------------------------------------------------------

    public function generateKey()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId  = (int) session()->get('user_id');
        $keyName = trim((string) $this->request->getPost('key_name'));

        if ($keyName === '') {
            return redirect()->back()->with('error', 'A key name is required.');
        }

        $apiKeyModel = new ApiKeyModel();

        // Prevent hoarding: cap active keys at 10 per user
        $activeCount = $apiKeyModel->where('user_id', $userId)->where('is_active', 1)->countAllResults();
        if ($activeCount >= 10) {
            return redirect()->back()->with('error', 'You have reached the maximum of 10 active API keys. Please revoke an existing key first.');
        }

        $rawKey = bin2hex(random_bytes(32)); // 64-char hex, cryptographically random

        $apiKeyModel->insert([
            'user_id'    => $userId,
            'key_name'   => $keyName,
            'api_key'    => $rawKey,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Show raw key once in flash – it will NOT be shown again
        return redirect()->to(base_url('developer'))->with('generated_api_key', $rawKey);
    }

    // -------------------------------------------------------------------------
    // Revoke an existing API key (soft-delete via is_active = 0)
    // -------------------------------------------------------------------------

    public function revoke($id)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $userId      = (int) session()->get('user_id');
        $apiKeyModel = new ApiKeyModel();
        $key         = $apiKeyModel->find((int) $id);

        if (!$key || (int) $key['user_id'] !== $userId) {
            return redirect()->to(base_url('developer'))->with('error', 'API key not found or access denied.');
        }

        $apiKeyModel->update((int) $id, ['is_active' => 0]);

        return redirect()->to(base_url('developer'))->with('success', 'API key "' . esc($key['key_name']) . '" has been revoked.');
    }

    // -------------------------------------------------------------------------
    // OpenAPI 3.0 specification (served at /openapi.json, consumed by Swagger UI)
    // -------------------------------------------------------------------------

    public function openApiJson()
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'       => 'Alumni Influencer API',
                'version'     => '1.0.0',
                'description' => 'Web API for the Alumni Influencer platform built by Phantasmagoria Ltd. '
                               . 'All /api/* endpoints require a Bearer token obtained from the Developer portal.',
            ],
            'servers' => [
                ['url' => base_url(), 'description' => 'Local development server'],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type'        => 'http',
                        'scheme'      => 'bearer',
                        'description' => 'API key generated from the Developer portal (/developer)',
                    ],
                ],
                'schemas' => [
                    'FeaturedAlumnus' => [
                        'type'       => 'object',
                        'properties' => [
                            'name'          => ['type' => 'string', 'example' => 'Jane Smith'],
                            'bio'           => ['type' => 'string', 'example' => 'Software engineer with 10 years experience.'],
                            'linkedin_url'  => ['type' => 'string', 'format' => 'uri', 'example' => 'https://linkedin.com/in/janesmith'],
                            'job_title_now' => ['type' => 'string', 'example' => 'Senior Software Engineer at Acme Corp'],
                            'profile_image' => ['type' => 'string', 'example' => 'abc123.jpg'],
                            'feature_date'  => ['type' => 'string', 'format' => 'date', 'example' => '2026-04-01'],
                            'winning_bid'   => ['type' => 'number', 'format' => 'float', 'example' => 250.00],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type'       => 'object',
                        'properties' => [
                            'status'  => ['type' => 'string', 'example' => 'error'],
                            'message' => ['type' => 'string', 'example' => 'Invalid bearer token'],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/api/featured' => [
                    'get' => [
                        'tags'        => ['Public API'],
                        'summary'     => "Get today's featured alumnus",
                        'description' => "Returns the full profile of the alumnus who won today's bidding slot. "
                                       . 'Returns 404 if no winner has been selected yet for today.',
                        'security'    => [['BearerAuth' => []]],
                        'responses'   => [
                            '200' => [
                                'description' => 'Featured alumnus returned successfully',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => [
                                            'type'       => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'string', 'example' => 'success'],
                                                'data'   => ['$ref' => '#/components/schemas/FeaturedAlumnus'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized – missing or invalid Bearer token',
                                'content'     => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                            '404' => [
                                'description' => 'No featured alumnus selected for today',
                                'content'     => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]],
                            ],
                        ],
                    ],
                ],

                '/register' => [
                    'post' => [
                        'tags'        => ['Authentication'],
                        'summary'     => 'Register a new alumni account',
                        'description' => 'Creates a new account. Only @iit.ac.lk email addresses are accepted. '
                                       . 'A verification email is sent before the account can be used.',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['name', 'email', 'role', 'password', 'confirm_password'],
                                        'properties' => [
                                            'name'             => ['type' => 'string', 'example' => 'Jane Smith'],
                                            'email'            => ['type' => 'string', 'format' => 'email', 'example' => 'jane@iit.ac.lk'],
                                            'role'             => ['type' => 'string', 'enum' => ['alumnus', 'developer']],
                                            'password'         => ['type' => 'string', 'format' => 'password'],
                                            'confirm_password' => ['type' => 'string', 'format' => 'password'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '302' => ['description' => 'Redirect to /login with success flash on success, or back with error flash on failure'],
                        ],
                    ],
                ],

                '/login' => [
                    'post' => [
                        'tags'        => ['Authentication'],
                        'summary'     => 'Log in to an existing account',
                        'description' => 'Authenticates the user and starts a session. Rate-limited to 5 attempts per 5 minutes.',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['email', 'password'],
                                        'properties' => [
                                            'email'    => ['type' => 'string', 'format' => 'email'],
                                            'password' => ['type' => 'string', 'format' => 'password'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '302' => ['description' => 'Redirect to /profile on success, or back with error flash on failure'],
                        ],
                    ],
                ],

                '/logout' => [
                    'get' => [
                        'tags'      => ['Authentication'],
                        'summary'   => 'Log out the current user',
                        'responses' => ['302' => ['description' => 'Redirect to /login']],
                    ],
                ],

                '/forgot-password' => [
                    'post' => [
                        'tags'        => ['Authentication'],
                        'summary'     => 'Request a password reset email',
                        'description' => 'Sends a password reset link to the registered email. Rate-limited to 3 attempts per 10 minutes. '
                                       . 'Always returns success to prevent email enumeration.',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['email'],
                                        'properties' => ['email' => ['type' => 'string', 'format' => 'email']],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['302' => ['description' => 'Redirect to /login with generic success message']],
                    ],
                ],

                '/reset-password' => [
                    'post' => [
                        'tags'        => ['Authentication'],
                        'summary'     => 'Reset password using a token from email',
                        'description' => 'Validates a single-use, expiring reset token and updates the password.',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['token', 'password', 'password_confirm'],
                                        'properties' => [
                                            'token'            => ['type' => 'string'],
                                            'password'         => ['type' => 'string', 'format' => 'password'],
                                            'password_confirm' => ['type' => 'string', 'format' => 'password'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['302' => ['description' => 'Redirect to /login on success']],
                    ],
                ],

                '/profile' => [
                    'get' => [
                        'tags'      => ['Profile'],
                        'summary'   => 'View your alumni profile',
                        'responses' => ['200' => ['description' => 'Profile view page']],
                    ],
                ],

                '/profile/manage' => [
                    'get' => [
                        'tags'      => ['Profile'],
                        'summary'   => 'Manage all profile sections (add/edit/delete credentials, employment, image)',
                        'responses' => ['200' => ['description' => 'Profile management page']],
                    ],
                ],

                '/profile/update' => [
                    'post' => [
                        'tags'      => ['Profile'],
                        'summary'   => 'Update basic profile details and/or profile photo',
                        'responses' => ['302' => ['description' => 'Redirect after update']],
                    ],
                ],

                '/profile/add-certification' => [
                    'post' => [
                        'tags'    => ['Profile'],
                        'summary' => 'Add a professional certification',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['certification_name'],
                                        'properties' => [
                                            'certification_name' => ['type' => 'string'],
                                            'organisation_name'  => ['type' => 'string'],
                                            'course_url'         => ['type' => 'string', 'format' => 'uri'],
                                            'completion_date'    => ['type' => 'string', 'format' => 'date'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['302' => ['description' => 'Redirect after add']],
                    ],
                ],

                '/profile/add-licence' => [
                    'post' => [
                        'tags'    => ['Profile'],
                        'summary' => 'Add a professional licence',
                        'responses' => ['302' => ['description' => 'Redirect after add']],
                    ],
                ],

                '/profile/add-degree' => [
                    'post' => [
                        'tags'    => ['Profile'],
                        'summary' => 'Add a degree',
                        'responses' => ['302' => ['description' => 'Redirect after add']],
                    ],
                ],

                '/profile/add-course' => [
                    'post' => [
                        'tags'    => ['Profile'],
                        'summary' => 'Add a short professional course',
                        'responses' => ['302' => ['description' => 'Redirect after add']],
                    ],
                ],

                '/profile/add-employment' => [
                    'post' => [
                        'tags'    => ['Profile'],
                        'summary' => 'Add an employment history entry',
                        'responses' => ['302' => ['description' => 'Redirect after add']],
                    ],
                ],

                '/bids' => [
                    'get' => [
                        'tags'        => ['Bidding'],
                        'summary'     => 'View your bids, current win/lose status, and monthly usage',
                        'description' => 'Shows blind bid status (winning/losing) without revealing the highest bid amount.',
                        'responses'   => ['200' => ['description' => 'Bidding page']],
                    ],
                ],

                '/bids/add' => [
                    'post' => [
                        'tags'        => ['Bidding'],
                        'summary'     => 'Place a new bid or increase an existing bid (blind)',
                        'description' => 'Bid amounts are blind – the highest competitor bid is never revealed. '
                                       . 'Updates are increase-only. Monthly limit of 3 wins enforced (4 with event attendance).',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['bid_amount'],
                                        'properties' => ['bid_amount' => ['type' => 'number', 'format' => 'float', 'minimum' => 0.01]],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['302' => ['description' => 'Redirect to /bids with success or error flash']],
                    ],
                ],

                '/bids/delete/{id}' => [
                    'get' => [
                        'tags'        => ['Bidding'],
                        'summary'     => 'Cancel a pending bid',
                        'description' => 'Only pending bids (not yet decided) can be cancelled.',
                        'parameters'  => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses'   => ['302' => ['description' => 'Redirect after cancellation']],
                    ],
                ],

                '/developer' => [
                    'get' => [
                        'tags'        => ['Developer'],
                        'summary'     => 'Developer dashboard – manage API keys and view usage statistics',
                        'description' => 'Shows all API keys, their status, and the last 50 API usage logs (endpoint, method, IP, timestamp).',
                        'responses'   => ['200' => ['description' => 'Developer dashboard page']],
                    ],
                ],

                '/developer/generate-key' => [
                    'post' => [
                        'tags'        => ['Developer'],
                        'summary'     => 'Generate a new API key',
                        'description' => 'Generates a cryptographically random 64-char bearer token. Maximum 10 active keys per user.',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'required'   => ['key_name'],
                                        'properties' => ['key_name' => ['type' => 'string', 'example' => 'My AR Client Key']],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['302' => ['description' => 'Redirect to /developer – key shown once in flash message']],
                    ],
                ],

                '/developer/revoke/{id}' => [
                    'get' => [
                        'tags'        => ['Developer'],
                        'summary'     => 'Revoke an API key',
                        'description' => 'Soft-deactivates the key (is_active = 0). Revoked keys are rejected by the API.',
                        'parameters'  => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses'   => ['302' => ['description' => 'Redirect to /developer']],
                    ],
                ],
            ],
        ];

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // -------------------------------------------------------------------------
    // Developer-facing alumni profile view
    // -------------------------------------------------------------------------

    public function profile($id = null)
    {
        $db     = \Config\Database::connect();
        $userId = $id ?? session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $user           = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $certifications = $db->table('certifications')->where('user_id', $userId)->get()->getResultArray();
        $degrees        = $db->table('degrees')->where('user_id', $userId)->get()->getResultArray();

        return view('developer/profile', [
            'user'           => $user,
            'certifications' => $certifications,
            'qualifications' => $degrees,
        ]);
    }
}
