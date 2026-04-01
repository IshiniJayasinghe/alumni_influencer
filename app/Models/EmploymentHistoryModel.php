<?php

namespace App\Models;

use CodeIgniter\Model;

class EmploymentHistoryModel extends Model
{
    protected $table = 'employment_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'company_name',
        'job_title',
        'start_date',
        'end_date',
        'is_current',
        'description',
    ];
}
