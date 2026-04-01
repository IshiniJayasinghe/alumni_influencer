<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'email',
        'password_hash',
        'role',
        'is_verified',
        'bio',
        'linkedin_url',
        'job_title_now',
        'profile_image',
        'extra_monthly_chance',
        'created_at',
        'updated_at',
    ];
}
