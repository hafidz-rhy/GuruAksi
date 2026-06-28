<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class TahunPelajaran extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $db = \Config\Database::connect();
        $data = $db->table('mst_thn_pelajaran')
            ->orderBy('is_aktif', 'DESC')
            ->orderBy('tgl_mulai', 'DESC')
            ->get()->getResult();

        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function show($id = null)
    {
        $db = \Config\Database::connect();
        $data = $db->table('mst_thn_pelajaran')->where('id', $id)->get()->getRow();
        if (! $data) return $this->failNotFound('Data tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $input = $this->request->getJSON(true);
        $insert = [
            'nama' => $input['nama'] ?? '',
            'tgl_mulai' => $input['tgl_mulai'] ?? '',
            'tgl_berakhir' => $input['tgl_berakhir'] ?? '',
            'is_aktif' => $input['is_aktif'] ?? false,
            'crd_at' => date('Y-m-d H:i:s'), 'upd_at' => date('Y-m-d H:i:s'),
        ];
        if ($db->table('mst_thn_pelajaran')->insert($insert)) {
            return $this->respondCreated(['status' => 'success', 'message' => 'Tahun pelajaran berhasil ditambahkan']);
        }
        return $this->fail('Gagal menambahkan data');
    }

    public function update($id = null)
    {
        $db = \Config\Database::connect();
        $input = $this->request->getJSON(true);
        $update = ['nama' => $input['nama'] ?? '', 'tgl_mulai' => $input['tgl_mulai'] ?? '',
                    'tgl_berakhir' => $input['tgl_berakhir'] ?? '', 'upd_at' => date('Y-m-d H:i:s')];
        $db->table('mst_thn_pelajaran')->where('id', $id)->update($update);
        return $this->respond(['status' => 'success', 'message' => 'Data berhasil diperbarui']);
    }

    public function delete($id = null)
    {
        $db = \Config\Database::connect();
        $refs = [];
        if ($db->table('jadwal_mengajar')->where('tahun_pelajaran_id', $id)->where('dlt_at', null)->countAllResults() > 0) $refs[] = 'Jadwal';
        if ($db->table('presensi_guru')->where('tahun_pelajaran_id', $id)->countAllResults() > 0) $refs[] = 'Presensi';
        if ($db->table('mst_kelas')->where('tahun_pelajaran_id', $id)->where('dlt_at', null)->countAllResults() > 0) $refs[] = 'Kelas';
        if (! empty($refs)) return $this->fail('Tidak dapat dihapus: masih digunakan di ' . implode(', ', $refs), 409);
        $db->table('mst_thn_pelajaran')->where('id', $id)->delete();
        return $this->respondDeleted(['status' => 'success', 'message' => 'Data berhasil dihapus']);
    }

    public function aktif()
    {
        $db = \Config\Database::connect();
        $data = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();
        if (! $data) $data = $db->table('mst_thn_pelajaran')->orderBy('tgl_mulai', 'DESC')->get()->getRow();
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function aktifkan($id = null)
    {
        $db = \Config\Database::connect();
        $db->table('mst_thn_pelajaran')->update(['is_aktif' => 0]);
        $db->table('mst_thn_pelajaran')->where('id', $id)->update(['is_aktif' => 1]);
        return $this->respond(['status' => 'success', 'message' => 'Tahun pelajaran berhasil diaktifkan']);
    }
}