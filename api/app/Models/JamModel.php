<?php

namespace App\Models;

use CodeIgniter\Model;

class JamModel extends Model
{
    protected $table            = 'mst_jam';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['kode', 'jam_mulai', 'jam_selesai', 'jenis', 'urutan', 'status'];
    protected $useTimestamps = true;
    protected $createdField  = 'crd_at';
    protected $updatedField  = 'upd_at';
    protected $deletedField  = 'dlt_at';
    protected $dateFormat    = 'datetime';
}