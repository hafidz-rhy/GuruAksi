<?php

namespace App\Models;

use CodeIgniter\Model;

class PatchHistoryModel extends Model
{
    protected $table            = 'patch_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'version', 'previous_version', 'file_name', 'file_size',
        'manifest', 'status', 'error_message', 'applied_by', 'applied_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'crd_at';
    protected $updatedField  = 'upd_at';
    protected $deletedField  = 'dlt_at';
    protected $dateFormat    = 'datetime';
}