<?php

namespace App\Models;

use CodeIgniter\Model;

class GuruModel extends Model
{
    protected $table            = 'mst_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'user_id', 'peg_id', 'nik', 'nm_lengkap', 'nm_panggil', 'mapel_id',
        'tmp_lahir', 'tgl_lahir', 'kelamin', 'agama', 'alamat',
        'no_telp', 'email', 'status', 'foto',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'crd_at';
    protected $updatedField  = 'upd_at';
    protected $deletedField  = 'dlt_at';
    protected $dateFormat    = 'datetime';
}