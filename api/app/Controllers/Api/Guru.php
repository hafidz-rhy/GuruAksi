<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\GuruModel;
use App\Models\UsersModel;
use CodeIgniter\API\ResponseTrait;

class Guru extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('mst_guru');
        $builder->select('mst_guru.*, users.username');
        $builder->join('users', 'users.id = mst_guru.user_id', 'left');
        $builder->where('mst_guru.dlt_at', null);
        $data = $builder->get()->getResult();
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function show($id = null)
    {
        $data = (new GuruModel())->find($id);
        if (! $data) return $this->failNotFound('Guru tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $rules = [
            'peg_id'     => 'required|min_length[3]|max_length[20]|is_unique[mst_guru.peg_id]',
            'nik'        => 'required|min_length[4]|max_length[30]',
            'nm_lengkap' => 'required|min_length[2]|max_length[150]',
            'mapel_id'   => 'required|integer|is_not_unique[mst_mapel.id]',
            'no_telp'    => 'required|min_length[6]|max_length[20]',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $data    = $this->request->getJSON(true);
        $model   = new GuruModel();
        $userModel = new UsersModel();

        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        $userId   = $data['user_id'] ?? null;

        if (! $userId && $username && $password) {
            $existing = $userModel->where('username', $username)->first();
            if ($existing) return $this->fail('Username sudah digunakan', 409);
            $userId = $userModel->insert([
                'username' => $username,
                'pwd'      => password_hash($password, PASSWORD_BCRYPT),
                'role'     => 'guru',
                'status'   => 'aktif',
            ], true);
            $data['user_id'] = $userId;
        }

        if ($model->insert($data)) {
            return $this->respondCreated([
                'status' => 'success', 'message' => 'Guru berhasil ditambahkan',
                'data' => $model->find($model->getInsertID()),
            ]);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function update($id = null)
    {
        $model = new GuruModel();
        $guru = $model->find($id);
        if (! $guru) return $this->failNotFound('Guru tidak ditemukan');

        $rules = [
            'peg_id'     => "min_length[3]|max_length[20]|is_unique[mst_guru.peg_id,id,{$id}]",
            'nik'        => 'min_length[4]|max_length[30]',
            'nm_lengkap' => 'min_length[2]|max_length[150]',
            'mapel_id'   => 'integer|is_not_unique[mst_mapel.id]',
            'no_telp'    => 'min_length[6]|max_length[20]',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $data = $this->request->getJSON(true);
        $userModel = new UsersModel();

        // Auto-create user account jika guru belum punya user_id dan username/password disediakan
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        $existingUserId = $data['user_id'] ?? $guru->user_id ?? null;

        if (! $existingUserId && $username && $password) {
            $existing = $userModel->where('username', $username)->first();
            if ($existing) return $this->fail('Username sudah digunakan', 409);
            $existingUserId = $userModel->insert([
                'username' => $username,
                'pwd'      => password_hash($password, PASSWORD_BCRYPT),
                'role'     => 'guru',
                'status'   => 'aktif',
            ], true);
            $data['user_id'] = $existingUserId;
        }

        if ($model->update($id, $data)) {
            return $this->respond(['status' => 'success', 'message' => 'Guru berhasil diperbarui']);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function delete($id = null)
    {
        $guru = (new GuruModel())->find($id);
        if ($guru && $guru->user_id) {
            (new UsersModel())->delete($guru->user_id);
        }
        (new GuruModel())->delete($id);
        return $this->respondDeleted(['status' => 'success', 'message' => 'Guru dan akun login berhasil dihapus']);
    }

    public function status($id = null)
    {
        $model = new GuruModel();
        if (! $model->find($id)) return $this->failNotFound('Guru tidak ditemukan');
        $status = $this->request->getVar('status') ?? 'nonaktif';
        $model->update($id, ['status' => $status]);
        return $this->respond(['status' => 'success', 'message' => 'Status guru diubah menjadi ' . $status]);
    }
}