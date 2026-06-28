<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Agenda extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $db = \Config\Database::connect();
        $role = $this->request->role;
        $userId = $this->request->user_id;
        $guruId = $this->request->getGet('guru_id');
        $status = $this->request->getGet('status');
        $tgl = $this->request->getGet('tgl');

        // Guru only sees own agenda
        if ($role === 'guru') {
            $guru = $db->table('mst_guru')->where('user_id', $userId)->get()->getRow();
            if (!$guru) return $this->failNotFound('Data guru tidak ditemukan');
            $guruId = $guru->id;
        }

        $builder = $db->table('agenda_guru a')
            ->select('a.*, g.nm_lengkap as guru_nama')
            ->join('mst_guru g', 'g.id = a.guru_id', 'left')
            ->orderBy('a.tgl', 'DESC')
            ->orderBy('a.jam', 'ASC');

        if ($guruId) $builder->where('a.guru_id', $guruId);
        if ($status) $builder->where('a.status', $status);
        if ($tgl) $builder->where('a.tgl', $tgl);

        $data = $builder->get()->getResult();

        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function show($id = null)
    {
        $db = \Config\Database::connect();
        $data = $db->table('agenda_guru a')
            ->select('a.*, g.nm_lengkap as guru_nama')
            ->join('mst_guru g', 'g.id = a.guru_id', 'left')
            ->where('a.id', $id)
            ->get()->getRow();

        if (!$data) return $this->failNotFound('Agenda tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $rules = [
            'guru_id' => 'required',
            'tgl'     => 'required',
            'judul'   => 'required|min_length[2]|max_length[200]',
        ];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $db = \Config\Database::connect();
        $data = $this->request->getJSON(true);
        $data['crd_at'] = date('Y-m-d H:i:s');
        $data['upd_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'pending';

        if ($db->table('agenda_guru')->insert($data)) {
            return $this->respondCreated([
                'status'  => 'success',
                'message' => 'Agenda berhasil ditambahkan',
                'data'    => $db->table('agenda_guru')->where('id', $db->insertID())->get()->getRow(),
            ]);
        }
        return $this->fail('Gagal menyimpan agenda');
    }

    public function update($id = null)
    {
        $db = \Config\Database::connect();
        $existing = $db->table('agenda_guru')->where('id', $id)->get()->getRow();
        if (!$existing) return $this->failNotFound('Agenda tidak ditemukan');

        $data = $this->request->getJSON(true);
        $data['upd_at'] = date('Y-m-d H:i:s');

        $db->table('agenda_guru')->where('id', $id)->update($data);
        return $this->respond(['status' => 'success', 'message' => 'Agenda diperbarui']);
    }

    public function delete($id = null)
    {
        $db = \Config\Database::connect();
        $db->table('agenda_guru')->where('id', $id)->delete();
        return $this->respondDeleted(['status' => 'success', 'message' => 'Agenda dihapus']);
    }

    /**
     * PATCH /api/agenda/:id/status
     * Quick toggle status (pending → selesai / selesai → pending)
     */
    public function toggleStatus($id = null)
    {
        $db = \Config\Database::connect();
        $existing = $db->table('agenda_guru')->where('id', $id)->get()->getRow();
        if (!$existing) return $this->failNotFound('Agenda tidak ditemukan');

        $new = $existing->status === 'selesai' ? 'pending' : 'selesai';
        $db->table('agenda_guru')->where('id', $id)->update([
            'status' => $new,
            'upd_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'  => 'success',
            'message' => "Status agenda diubah ke $new",
            'data'    => ['status' => $new],
        ]);
    }
}