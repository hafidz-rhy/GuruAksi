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
        $guruModel   = new GuruModel();
        $siswaModel  = new SiswaModel();
        $kelasModel  = new \App\Models\KelasModel();
        $db          = \Config\Database::connect();

        $presensiHariIni = $db->table('presensi_guru')
            ->where('tgl', date('Y-m-d'))
            ->where('status', 'hadir')
            ->countAllResults();

        $tpAktif = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'widget1' => $guruModel->where('status', 'aktif')->countAllResults(false),
                    'widget2' => $siswaModel->where('status', 'aktif')->countAllResults(false),
                    'widget3' => $kelasModel->where('status', 'aktif')->countAllResults(false),
                    'widget4' => $presensiHariIni,
                ],
                'tp_aktif' => $tpAktif ? $tpAktif->nama : '',
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

        $db = \Config\Database::connect();
        $hariIni = $this->getHariIni(); // 'Senin', 'Selasa', etc.
        $tglSekarang = date('Y-m-d');
        $bulanIni = date('m');
        $tahunIni = date('Y');

        // Widget 1: Jadwal Hari Ini
        $jadwalHariIni = $db->table('jadwal_mengajar')
            ->select('jadwal_mengajar.*, mst_jam.jam_mulai, mst_jam.jam_selesai, mst_mapel.nm_mapel as mapel, mst_kelas.nm_kelas as kelas')
            ->join('mst_jam', 'mst_jam.id = jadwal_mengajar.jam_id', 'left')
            ->join('mst_mapel', 'mst_mapel.id = jadwal_mengajar.mapel_id', 'left')
            ->join('mst_kelas', 'mst_kelas.id = jadwal_mengajar.kelas_id', 'left')
            ->where('jadwal_mengajar.guru_id', $guru->id)
            ->where('jadwal_mengajar.hari', $hariIni)
            ->where('jadwal_mengajar.status', 'aktif')
            ->orderBy('mst_jam.urutan', 'ASC')
            ->get()->getResult();

        $jadwalFormatted = [];
        foreach ($jadwalHariIni as $j) {
            $jadwalFormatted[] = [
                'id'    => $j->id,
                'jam'   => ($j->jam_mulai ?? '') . ' - ' . ($j->jam_selesai ?? ''),
                'mapel' => $j->mapel ?? '-',
                'kelas' => $j->kelas ?? '-',
            ];
        }

        // Widget 2: Kehadiran Bulan Ini
        $kehadiranBulanIni = $db->table('presensi_guru')
            ->where('guru_id', $guru->id)
            ->where('MONTH(tgl)', $bulanIni)
            ->where('YEAR(tgl)', $tahunIni)
            ->where('status', 'hadir')
            ->countAllResults();

        // Widget 3: Jurnal Terisi Bulan Ini
        $jurnalBulanIni = $db->table('jurnal_mengajar')
            ->where('guru_id', $guru->id)
            ->where('MONTH(tgl)', $bulanIni)
            ->where('YEAR(tgl)', $tahunIni)
            ->countAllResults();

        // Widget 4: Agenda Minggu Ini
        $agendaMingguIni = $db->table('agenda_guru')
            ->where('guru_id', $guru->id)
            ->where('tgl >=', $tglSekarang)
            ->where('tgl <=', date('Y-m-d', strtotime('+7 days')))
            ->countAllResults();

        $tpAktif = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'widget1' => count($jadwalFormatted),
                    'widget2' => $kehadiranBulanIni,
                    'widget3' => $jurnalBulanIni,
                    'widget4' => $agendaMingguIni,
                ],
                'jadwal_hari_ini' => $jadwalFormatted,
                'jurnal_terbaru'   => [],
                'tp_aktif'         => $tpAktif ? $tpAktif->nama : '',
            ],
        ]);
    }

    public function kamad()
    {
        $guruModel   = new GuruModel();
        $siswaModel  = new SiswaModel();
        $kelasModel  = new \App\Models\KelasModel();
        $db          = \Config\Database::connect();

        $hariIni = $this->getHariIni();
        $tglSekarang = date('Y-m-d');
        $bulanIni = date('m');
        $tahunIni = date('Y');

        // Widget 1: Data Siswa Aktif
        $siswaAktif = $siswaModel->where('status', 'aktif')->countAllResults(false);

        // Widget 2: Data Guru Aktif
        $guruAktif = $guruModel->where('status', 'aktif')->countAllResults(false);

        // Widget 3: Jurnal Terisi Hari Ini (rekap semua guru)
        $jurnalHariIni = $db->table('jurnal_mengajar')
            ->where('tgl', $tglSekarang)
            ->countAllResults();

        // Widget 4: Jadwal Hari Ini
        $jadwalHariIni = $db->table('jadwal_mengajar')
            ->where('hari', $hariIni)
            ->where('status', 'aktif')
            ->countAllResults();

        $tpAktif = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'widget1' => $siswaAktif,
                    'widget2' => $guruAktif,
                    'widget3' => $jurnalHariIni,
                    'widget4' => $jadwalHariIni,
                ],
                'tp_aktif' => $tpAktif ? $tpAktif->nama : '',
            ],
        ]);
    }

    /**
     * Get current day in Indonesian with capital first letter
     * (matches DB enum: 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')
     */
    private function getHariIni(): string
    {
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $index = (int) date('w');
        return $hari[$index];
    }
}