<?php

namespace App\Models;

use CodeIgniter\Model;

class FeaturedWinnerModel extends Model
{
    protected $table = 'featured_winners';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'bid_id',
        'feature_date',
        'winning_bid',
    ];
}
