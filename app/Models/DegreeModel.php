<?php

namespace App\Models;

use CodeIgniter\Model;

class DegreeModel extends Model
{
    protected $table = 'degrees';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'degree_name',
        'institution_name',
        'official_url',
        'completion_date',
    ];
}
