<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SiswaModel;
use CodeIgniter\API\ResponseTrait;

class Siswa extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new SiswaModel();
        $data  = $model->orderBy('kelas_id', 'ASC')->orderBy('nm_lengkap', 'ASC')->findAll();

        return $this->respond([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function show($id = null)
    {
        $model = new SiswaModel();
        $data  = $model->find($id);

        if (! $data) {
            return $this->failNotFound('Siswa tidak ditemukan');
        }

        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $model = new SiswaModel();
        $data  = $this->request->getJSON(true);

        if ($model->insert($data)) {
            return $this->respondCreated([
                'status'  => 'success',
                'message' => 'Siswa berhasil ditambahkan',
                'data'    => $model->find($model->getInsertID()),
            ]);
        }

        return $this->failValidationErrors($model->errors());
    }

    public function update($id = null)
    {
        $model = new SiswaModel();
        $data  = $this->request->getJSON(true);

        if ($model->update($id, $data)) {
            return $this->respond(['status' => 'success', 'message' => 'Siswa berhasil diperbarui']);
        }

        return $this->failValidationErrors($model->errors());
    }

    public function delete($id = null)
    {
        $model = new SiswaModel();
        $model->delete($id);

        return $this->respondDeleted(['status' => 'success', 'message' => 'Siswa berhasil dihapus']);
    }

    /**
     * POST /api/siswa/naik-kelas
     * Naik kelas massal
     */
    public function naikKelas()
    {
        $thnPelajaranId = $this->request->getVar('thn_pelajaran_id');
        $kelasAsal      = $this->request->getVar('kelas_asal');
        $kelasTujuan    = $this->request->getVar('kelas_tujuan');

        if (! $thnPelajaranId || ! $kelasAsal || ! $kelasTujuan) {
            return $this->failValidationErrors('Parameter tidak lengkap');
        }

        $model = new SiswaModel();
        $model->where('kelas_id', $kelasAsal)
              ->where('status', 'aktif')
              ->set(['kelas_id' => $kelasTujuan])
              ->update();

        return $this->respond([
            'status'  => 'success',
            'message' => 'Siswa berhasil naik kelas',
        ]);
    }
}