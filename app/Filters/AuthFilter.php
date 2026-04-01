<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\ApiKeyModel;
use App\Models\ApiUsageLogModel;

/**
 * AuthFilter
 *
 * Validates a Bearer token on protected API routes.
 * Also logs each authenticated request to api_usage_logs.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Missing bearer token']);
        }

        $token       = trim(substr($authHeader, 7));
        $apiKeyModel = new ApiKeyModel();
        $apiKey      = $apiKeyModel->where('api_key', $token)->where('is_active', 1)->first();

        if (!$apiKey) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Invalid or revoked bearer token']);
        }

        // Log the authenticated request
        (new ApiUsageLogModel())->insert([
            'api_key_id' => $apiKey['id'],
            'endpoint'   => $request->getPath(),
            'method'     => $request->getMethod(),
            'client_ip'  => (string) $request->getIPAddress(),
            'used_at'    => date('Y-m-d H:i:s'),
        ]);

        $apiKeyModel->update($apiKey['id'], ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed after the request
    }
}
