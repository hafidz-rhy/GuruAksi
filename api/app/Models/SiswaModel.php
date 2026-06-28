<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
    protected $table            = 'mst_siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'nisn', 'nis', 'nm_lengkap', 'nm_panggil', 'kelas_id',
        'tmp_lahir', 'tgl_lahir', 'kelamin', 'agama', 'alamat',
        'no_telp', 'email', 'status', 'foto',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'crd_at';
    protected $updatedField  = 'upd_at';
    protected $deletedField  = 'dlt_at';
    protected $dateFormat    = 'datetime';
}