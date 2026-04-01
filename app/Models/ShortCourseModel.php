<?php

namespace App\Models;

use CodeIgniter\Model;

class ShortCourseModel extends Model
{
    protected $table = 'short_courses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'course_name',
        'provider_name',
        'course_url',
        'completion_date',
    ];
}
