<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\KelasModel;
use CodeIgniter\API\ResponseTrait;

class Kelas extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new KelasModel();
        $tahun = $this->request->getGet('tahun_pelajaran_id');
        if ($tahun) {
            $data = $model->where('tahun_pelajaran_id', $tahun)->orderBy('nm_kelas', 'ASC')->findAll();
        } else {
            $data = $model->orderBy('nm_kelas', 'ASC')->findAll();
        }

        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function show($id = null)
    {
        $model = new KelasModel();
        $data  = $model->find($id);
        if (! $data) return $this->failNotFound('Kelas tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $model = new KelasModel();
        $data  = $this->request->getJSON(true);

        // Auto-generate kode kelas (tidak required, boleh kosong)
        $tingkat = $data['tingkat'] ?? '';
        $jurusan = $data['jurusan'] ?? '';
        if ($tingkat) {
            $data['kode_kelas'] = $tingkat . ($jurusan ? '-' . $jurusan : '');
        }

        $rules = [
            'nm_kelas'           => 'required|min_length[2]|max_length[50]',
            'tahun_pelajaran_id' => 'required',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        if ($model->insert($data)) {
            return $this->respondCreated([
                'status' => 'success', 'message' => 'Kelas berhasil ditambahkan',
                'data' => $model->find($model->getInsertID()),
            ]);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function update($id = null)
    {
        $model = new KelasModel();
        if (! $model->find($id)) return $this->failNotFound('Kelas tidak ditemukan');

        $data = $this->request->getJSON(true);

        // Auto-generate kode kelas jika tingkat diubah
        if (isset($data['tingkat'])) {
            $tingkat = $data['tingkat'];
            $jurusan = $data['jurusan'] ?? '';
            if ($tingkat) {
                $data['kode_kelas'] = $tingkat . ($jurusan ? '-' . $jurusan : '');
            }
        }

        if ($model->update($id, $data)) {
            return $this->respond(['status' => 'success', 'message' => 'Kelas berhasil diperbarui']);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function delete($id = null)
    {
        $db = \Config\Database::connect();

        $siswaExists = $db->table('mst_siswa')
            ->where('kelas_id', $id)
            ->where('dlt_at', null)
            ->countAllResults();

        if ($siswaExists > 0) {
            return $this->fail('Kelas tidak dapat dihapus karena masih digunakan oleh ' . $siswaExists . ' siswa', 409);
        }

        (new KelasModel())->delete($id);
        return $this->respondDeleted(['status' => 'success', 'message' => 'Kelas berhasil dihapus']);
    }

    /**
     * GET /api/kelas/jenjang
     */
    public function jenjang()
    {
        $db = \Config\Database::connect();
        $jenjang = $db->table('pengaturan')->where('kunci', 'jenjang_sekolah')->get()->getRow();

        $jenjangValue = $jenjang ? $jenjang->nilai : 'SMP/MTs';

        $tingkatMap = [
            'SD/MI'    => ['1', '2', '3', '4', '5', '6'],
            'SMP/MTs'  => ['7', '8', '9'],
            'SMA/MA'   => ['10', '11', '12'],
        ];

        $tingkat = $tingkatMap[$jenjangValue] ?? ['7', '8', '9'];

        return $this->respond([
            'status'  => 'success',
            'data'    => [
                'jenjang' => $jenjangValue,
                'tingkat' => $tingkat,
            ],
        ]);
    }
}