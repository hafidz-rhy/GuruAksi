<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use CodeIgniter\API\ResponseTrait;

class Users extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $data = (new UsersModel())->findAll();
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'pwd'      => 'required|min_length[6]',
            'role'     => 'required|in_list[admin,guru,kamad]',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $data = $this->request->getJSON(true);
        $data['pwd'] = password_hash($data['pwd'], PASSWORD_BCRYPT);
        $data['status'] = $data['status'] ?? 'aktif';

        $model = new UsersModel();
        if ($model->insert($data)) {
            return $this->respondCreated(['status' => 'success', 'message' => 'User berhasil ditambahkan', 'data' => $model->find($model->getInsertID())]);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function update($id = null)
    {
        $model = new UsersModel();
        $existing = $model->find($id);
        if (! $existing) return $this->failNotFound('User tidak ditemukan');

        $data = $this->request->getJSON(true);
        // Don't allow password change via update (use reset-password)
        unset($data['pwd']);

        if ($model->update($id, $data)) {
            return $this->respond(['status' => 'success', 'message' => 'User berhasil diperbarui']);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function delete($id = null)
    {
        $model = new UsersModel();
        $existing = $model->find($id);
        if (! $existing) return $this->failNotFound('User tidak ditemukan');

        // Prevent self-delete
        if ($this->request->user_id == $id) {
            return $this->fail('Tidak dapat menghapus akun sendiri', 403);
        }

        // Prevent deleting the last admin
        if ($existing->role === 'admin') {
            $adminCount = $model->where('role', 'admin')->where('status', 'aktif')->countAllResults();
            if ($adminCount <= 1) {
                return $this->fail('Tidak dapat menghapus admin terakhir', 403);
            }
        }

        $model->delete($id);
        return $this->respondDeleted(['status' => 'success', 'message' => 'User berhasil dihapus']);
    }

    /**
     * PATCH /api/users/:id/reset-password
     * Reset password to default "guru123"
     */
    public function resetPassword($id = null)
    {
        $model = new UsersModel();
        if (! $model->find($id)) return $this->failNotFound('User tidak ditemukan');

        $model->update($id, ['pwd' => password_hash('guru123', PASSWORD_BCRYPT)]);
        return $this->respond(['status' => 'success', 'message' => 'Password direset ke default (guru123)']);
    }
}