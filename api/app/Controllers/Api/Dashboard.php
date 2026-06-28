<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\GuruModel;
use App\Models\SiswaModel;
use CodeIgniter\API\ResponseTrait;

class Dashboard extends BaseController
{
    use ResponseTrait;

    public function admin()
    {
        $guruModel  = new GuruModel();
        $siswaModel = new SiswaModel();
        $jadwalModel = new \App\Models\JadwalModel();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'guru'    => $guruModel->where('status', 'aktif')->countAllResults(false),
                    'siswa'   => $siswaModel->where('status', 'aktif')->countAllResults(false),
                    'jadwal'  => $jadwalModel->countAllResults(false),
                    'presensi' => 0,
                ],
            ],
        ]);
    }

    public function guru()
    {
        $guruModel = new GuruModel();
        $guru = $guruModel->where('user_id', $this->request->user_id)->first();

        if (! $guru) {
            return $this->failNotFound('Data guru tidak ditemukan');
        }

        $jadwalModel = new \App\Models\JadwalModel();
        $hariIni = $this->getHariIni();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'guru'    => 0,
                    'siswa'   => 0,
                    'jadwal'  => $jadwalModel->where('guru_id', $guru->id)->where('hari', $hariIni)->countAllResults(false),
                    'presensi' => 0,
                ],
                'jadwal_hari_ini' => $jadwalModel->where('guru_id', $guru->id)->where('hari', $hariIni)->findAll(),
            ],
        ]);
    }

    public function kamad()
    {
        $guruModel  = new GuruModel();
        $siswaModel = new SiswaModel();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'guru'    => $guruModel->where('status', 'aktif')->countAllResults(false),
                    'siswa'   => $siswaModel->where('status', 'aktif')->countAllResults(false),
                    'jadwal'  => 0,
                    'presensi' => 0,
                ],
            ],
        ]);
    }

    private function getHariIni(): string
    {
        $hari = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
        $index = (int) date('w');
        return $hari[$index];
    }
}