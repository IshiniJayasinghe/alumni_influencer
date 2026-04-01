<?php

namespace App\Models;

use CodeIgniter\Model;

class CertificationModel extends Model
{
    protected $table = 'certifications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'certification_name',
        'organisation_name',
        'course_url',
        'completion_date',
        'sponsor_offer',
        'is_sponsored',
    ];
}
