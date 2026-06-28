<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Jurnal extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $db = \Config\Database::connect();
        $tpId = $this->request->getGet('tahun_pelajaran_id');
        $guruId = $this->request->getGet('guru_id');
        $kelasId = $this->request->getGet('kelas_id');
        $role = $this->request->role;
        $userId = $this->request->user_id;

        // For guru role, filter by own data
        if ($role === 'guru') {
            $guru = $db->table('mst_guru')->where('user_id', $userId)->get()->getRow();
            if (! $guru) return $this->failNotFound('Data guru tidak ditemukan');
            $guruId = $guru->id;
        }

        $builder = $db->table('jurnal_mengajar j')
            ->select('j.*, g.nm_lengkap as guru_nama, k.nm_kelas, m.nm_mapel')
            ->join('mst_guru g', 'g.id = j.guru_id', 'left')
            ->join('mst_kelas k', 'k.id = j.kelas_id', 'left')
            ->join('mst_mapel m', 'm.id = j.mapel_id', 'left')
            ->orderBy('j.tgl', 'DESC');

        if ($tpId) $builder->where('j.tahun_pelajaran_id', $tpId);
        if ($guruId) $builder->where('j.guru_id', $guruId);
        if ($kelasId) $builder->where('j.kelas_id', $kelasId);

        $data = $builder->get()->getResult();

        // Group by guru for card view
        $guruJurnal = [];
        foreach ($data as $row) {
            $gid = $row->guru_id;
            if (!isset($guruJurnal[$gid])) {
                $guruJurnal[$gid] = [
                    'guru_id' => $gid,
                    'guru_nama' => $row->guru_nama,
                    'total' => 0,
                ];
            }
            $guruJurnal[$gid]['total']++;
        }

        return $this->respond([
            'status' => 'success',
            'data' => ['items' => $data, 'guru_stats' => array_values($guruJurnal)],
        ]);
    }

    public function show($id = null)
    {
        $db = \Config\Database::connect();
        $data = $db->table('jurnal_mengajar j')
            ->select('j.*, g.nm_lengkap as guru_nama, k.nm_kelas, m.nm_mapel')
            ->join('mst_guru g', 'g.id = j.guru_id', 'left')
            ->join('mst_kelas k', 'k.id = j.kelas_id', 'left')
            ->join('mst_mapel m', 'm.id = j.mapel_id', 'left')
            ->where('j.id', $id)->get()->getRow();
        if (! $data) return $this->failNotFound('Jurnal tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        if ($this->request->role !== 'guru') return $this->failForbidden('Hanya guru yang dapat menambah jurnal');
        $rules = ['guru_id' => 'required', 'tgl' => 'required'];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $db = \Config\Database::connect();
        $data = $this->request->getJSON(true);

        // Auto-fill TP
        if (empty($data['tahun_pelajaran_id'])) {
            $activeTp = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();
            $data['tahun_pelajaran_id'] = $activeTp ? $activeTp->id : null;
        }

        // Siswa tidak hadir as JSON
        if (!empty($data['siswa_tidak_hadir']) && is_array($data['siswa_tidak_hadir'])) {
            $data['siswa_tidak_hadir'] = json_encode($data['siswa_tidak_hadir']);
        }

        $data['crd_at'] = date('Y-m-d H:i:s');
        $data['upd_at'] = date('Y-m-d H:i:s');

        if ($db->table('jurnal_mengajar')->insert($data)) {
            return $this->respondCreated([
                'status' => 'success', 'message' => 'Jurnal berhasil ditambahkan',
                'data' => $db->table('jurnal_mengajar')->where('id', $db->insertID())->get()->getRow(),
            ]);
        }
        return $this->fail('Gagal menyimpan jurnal');
    }

    public function update($id = null)
    {
        if ($this->request->role !== 'guru') return $this->failForbidden('Hanya guru yang dapat mengubah jurnal');
        $db = \Config\Database::connect();
        $data = $this->request->getJSON(true);
        if (!empty($data['siswa_tidak_hadir']) && is_array($data['siswa_tidak_hadir'])) {
            $data['siswa_tidak_hadir'] = json_encode($data['siswa_tidak_hadir']);
        }
        $data['upd_at'] = date('Y-m-d H:i:s');
        $db->table('jurnal_mengajar')->where('id', $id)->update($data);
        return $this->respond(['status' => 'success', 'message' => 'Jurnal diperbarui']);
    }

    public function delete($id = null)
    {
        if ($this->request->role !== 'guru') return $this->failForbidden('Hanya guru yang dapat menghapus jurnal');
        $db = \Config\Database::connect();
        $db->table('jurnal_mengajar')->where('id', $id)->delete();
        return $this->respondDeleted(['status' => 'success', 'message' => 'Jurnal dihapus']);
    }
}