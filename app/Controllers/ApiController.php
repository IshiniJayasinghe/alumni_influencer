<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;
use App\Models\ApiUsageLogModel;
use App\Models\FeaturedWinnerModel;

class ApiController extends BaseController
{
    public function featuredToday()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (! $authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Missing bearer token']);
        }

        $token = trim(substr($authHeader, 7));
        $apiKeyModel = new ApiKeyModel();
        $apiKey = $apiKeyModel->where('api_key', $token)->where('is_active', 1)->first();

        if (! $apiKey) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Invalid bearer token']);
        }

        $winner = (new FeaturedWinnerModel())
            ->select('featured_winners.feature_date, featured_winners.winning_bid, users.name, users.bio, users.linkedin_url, users.job_title_now, users.profile_image')
            ->join('users', 'users.id = featured_winners.user_id', 'left')
            ->where('feature_date', date('Y-m-d'))
            ->first();

        (new ApiUsageLogModel())->insert([
            'api_key_id' => $apiKey['id'],
            'endpoint' => '/api/featured',
            'method' => 'GET',
            'client_ip' => (string) $this->request->getIPAddress(),
            'used_at' => date('Y-m-d H:i:s'),
        ]);

        $apiKeyModel->update($apiKey['id'], ['last_used_at' => date('Y-m-d H:i:s')]);

        if (! $winner) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'No featured alumnus selected for today']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $winner,
        ]);
    }
}
