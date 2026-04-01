<?php

namespace App\Controllers;

use App\Models\FeaturedWinnerModel;
use App\Models\UserModel;

class Home extends BaseController
{
    public function index()
    {
        $winnerModel = new FeaturedWinnerModel();
        $winner = $winnerModel
            ->select('featured_winners.*, users.name, users.bio, users.linkedin_url, users.job_title_now, users.profile_image')
            ->join('users', 'users.id = featured_winners.user_id', 'left')
            ->where('feature_date', date('Y-m-d'))
            ->first();

        return view('home', ['winner' => $winner]);
    }
}
