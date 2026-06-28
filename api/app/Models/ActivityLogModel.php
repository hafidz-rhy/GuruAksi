<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table            = 'activity_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'user_id', 'username', 'role', 'action', 'description',
        'ip_address', 'user_agent',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'crd_at';
    protected $updatedField  = '';
    protected $dateFormat    = 'datetime';
}
