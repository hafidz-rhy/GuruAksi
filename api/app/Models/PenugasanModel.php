<?php

namespace App\Models;

use CodeIgniter\Model;

class PenugasanModel extends Model
{
    protected $table            = 'penugasan_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'tahun_pelajaran_id', 'guru_id', 'mapel_id', 'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'crd_at';
    protected $updatedField  = 'upd_at';
    protected $deletedField  = 'dlt_at';
    protected $dateFormat    = 'datetime';
}