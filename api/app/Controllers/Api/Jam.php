<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\JamModel;
use CodeIgniter\API\ResponseTrait;

class Jam extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $data = (new JamModel())->orderBy('urutan', 'ASC')->findAll();
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function show($id = null)
    {
        $data = (new JamModel())->find($id);
        if (! $data) return $this->failNotFound('Jam tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $rules = ['kode' => 'required|min_length[1]|max_length[5]', 'jam_mulai' => 'required', 'jam_selesai' => 'required'];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        $model = new JamModel();
        $data  = $this->request->getJSON(true);
        if ($model->insert($data)) {
            return $this->respondCreated(['status' => 'success', 'message' => 'Jam berhasil ditambahkan', 'data' => $model->find($model->getInsertID())]);
        }
        return $this->failValidationErrors($model->errors());
    }

    public function update($id = null)
    {
        $model = new JamModel();
        if (! $model->find($id)) return $this->failNotFound('Jam tidak ditemukan');
        $data = $this->request->getJSON(true);
        if ($model->update($id, $data)) return $this->respond(['status' => 'success', 'message' => 'Jam berhasil diperbarui']);
        return $this->failValidationErrors($model->errors());
    }

    public function delete($id = null)
    {
        (new JamModel())->delete($id);
        return $this->respondDeleted(['status' => 'success', 'message' => 'Jam berhasil dihapus']);
    }

    /**
     * POST /api/jam/generate
     * Generate template slot jam otomatis
     */
    public function generate()
    {
        $hari         = $this->request->getVar('hari') ?? 6;
        $jamPerHari   = (int)($this->request->getVar('jam_per_hari') ?? 8);
        $durasi       = (int)($this->request->getVar('durasi') ?? 40);
        $istirahat    = (int)($this->request->getVar('durasi_istirahat') ?? 30);
        $istirahatSet = (int)($this->request->getVar('istirahat_setelah') ?? 4);
        $start        = $this->request->getVar('jam_mulai') ?? '07:00';

        $model = new JamModel();
        // Hapus semua jam existing
        $model->where('id >', 0)->delete();

        $startMinutes = $this->timeToMinutes($start);
        $current = $startMinutes;
        $urutan = 1;
        $inserted = [];

        for ($i = 1; $i <= $jamPerHari; $i++) {
            $jenis = ($i === $istirahatSet + 1) ? 'istirahat' : 'pelajaran';
            $dur  = ($jenis === 'istirahat') ? $istirahat : $durasi;
            $end  = $current + $dur;

            $data = [
                'kode'        => 'J' . $i,
                'jam_mulai'   => $this->minutesToTime($current),
                'jam_selesai' => $this->minutesToTime($end),
                'jenis'       => $jenis,
                'urutan'      => $urutan++,
                'status'      => 'aktif',
            ];
            $model->insert($data);
            $inserted[] = $model->find($model->getInsertID());
            $current = $end;
            // Kembalikan ke pelajaran setelah istirahat
            // (jenis akan otomatis kembali ke pelajaran di iterasi berikutnya karena cek i === istirahatSet+1)
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'Template jam berhasil dibuat (' . count($inserted) . ' slot)',
            'data'    => $inserted,
        ]);
    }

    private function timeToMinutes($time) {
        list($h, $m) = explode(':', $time);
        return (int)$h * 60 + (int)$m;
    }

    private function minutesToTime($minutes) {
        return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
    }
}