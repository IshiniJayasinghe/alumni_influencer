<?php

namespace App\Models;

use CodeIgniter\Model;

class ProfessionalLicenceModel extends Model
{
    protected $table = 'professional_licences';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'licence_name',
        'awarding_body',
        'official_url',
        'completion_date',
        'sponsor_offer',
        'is_sponsored',
    ];
}
