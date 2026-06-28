<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MapelModel;
use CodeIgniter\API\ResponseTrait;

class Mapel extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new MapelModel();
        $data  = $model->orderBy('nm_mapel', 'ASC')->findAll();

        return $this->respond([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function show($id = null)
    {
        $model = new MapelModel();
        $data  = $model->find($id);

        if (! $data) {
            return $this->failNotFound('Mata Pelajaran tidak ditemukan');
        }

        return $this->respond([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function create()
    {
        $rules = [
            'kode'     => 'required|min_length[2]|max_length[10]|is_unique[mst_mapel.kode]',
            'nm_mapel' => 'required|min_length[2]|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = new MapelModel();
        $data  = $this->request->getJSON(true);

        if ($model->insert($data)) {
            return $this->respondCreated([
                'status'  => 'success',
                'message' => 'Mata Pelajaran berhasil ditambahkan',
                'data'    => $model->find($model->getInsertID()),
            ]);
        }

        return $this->failValidationErrors($model->errors());
    }

    public function update($id = null)
    {
        $model = new MapelModel();
        $existing = $model->find($id);
        if (! $existing) {
            return $this->failNotFound('Mata Pelajaran tidak ditemukan');
        }

        $rules = [
            'kode'     => 'min_length[2]|max_length[10]|is_unique[mst_mapel.kode,id,' . $id . ']',
            'nm_mapel' => 'min_length[2]|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);
        if ($model->update($id, $data)) {
            return $this->respond([
                'status'  => 'success',
                'message' => 'Mata Pelajaran berhasil diperbarui',
            ]);
        }

        return $this->failValidationErrors($model->errors());
    }

    public function delete($id = null)
    {
        $model = new MapelModel();
        $model->delete($id);

        return $this->respondDeleted([
            'status'  => 'success',
            'message' => 'Mata Pelajaran berhasil dihapus',
        ]);
    }
}