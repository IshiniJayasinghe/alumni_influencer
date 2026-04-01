<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiUsageLogModel extends Model
{
    protected $table = 'api_usage_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'api_key_id',
        'endpoint',
        'method',
        'client_ip',
        'used_at',
    ];
}
