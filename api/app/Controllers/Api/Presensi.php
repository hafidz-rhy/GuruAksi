<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Presensi extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $db = \Config\Database::connect();
        $role = $this->request->role;
        $userId = $this->request->user_id;
        $tpId = $this->request->getGet('tahun_pelajaran_id');

        if ($role === 'guru') {
            $guru = $db->table('mst_guru')->where('user_id', $userId)->get()->getRow();
            if (! $guru) return $this->failNotFound('Data guru tidak ditemukan');
            $guruIds = [$guru->id];
        } else {
            $query = $db->table('mst_guru')->where('dlt_at', null);
            $guruList = $query->get()->getResult();
            $guruIds = array_column($guruList, 'id');
        }

        $stats = [];
        foreach ($guruIds as $gid) {
            $builder = $db->table('presensi_guru')->where('guru_id', $gid);
            if ($tpId) $builder->where('tahun_pelajaran_id', $tpId);
            $hadir = $builder->where('status', 'hadir')->countAllResults();
            $izin  = (clone $builder)->where('status', 'izin')->countAllResults();
            $sakit = (clone $builder)->where('status', 'sakit')->countAllResults();
            $alpha = (clone $builder)->where('status', 'alpha')->countAllResults();
            $stats[$gid] = ['hadir' => $hadir, 'izin' => $izin, 'sakit' => $sakit, 'alpha' => $alpha];
        }

        $guruData = $db->table('mst_guru')->whereIn('id', $guruIds)->where('dlt_at', null)->get()->getResult();

        return $this->respond(['status' => 'success', 'data' => ['guru' => $guruData, 'stats' => $stats]]);
    }

    public function riwayat($guruId = null)
    {
        $db = \Config\Database::connect();
        $tpId = $this->request->getGet('tahun_pelajaran_id');
        $guru = $db->table('mst_guru')->where('id', $guruId)->get()->getRow();
        if (! $guru) return $this->failNotFound('Guru tidak ditemukan');

        $builder = $db->table('presensi_guru')
            ->select('id, guru_id, tahun_pelajaran_id, tgl, jam_dtg as jam, status, ket as keterangan, metode, crd_at')
            ->where('guru_id', $guruId);
        if ($tpId) $builder->where('tahun_pelajaran_id', $tpId);
        $riwayat = $builder->orderBy('tgl', 'DESC')->orderBy('jam_dtg', 'DESC')->get()->getResult();

        return $this->respond(['status' => 'success', 'data' => ['guru' => $guru, 'riwayat' => $riwayat]]);
    }

    public function create()
    {
        $rules = [
            'guru_id' => 'required',
            'tgl'     => 'required',
            'jam'     => 'required',
            'status'  => 'required|in_list[hadir,izin,sakit,alpha]',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $db = \Config\Database::connect();
        $guruId  = $this->request->getVar('guru_id');
        $tgl     = $this->request->getVar('tgl');
        $jam     = $this->request->getVar('jam');
        $status  = $this->request->getVar('status');
        $ket     = $this->request->getVar('keterangan') ?? '';

        // Get active TP
        $activeTp = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();
        $tpId = $activeTp ? $activeTp->id : null;

        $exist = $db->table('presensi_guru')
            ->where('guru_id', $guruId)
            ->where('tgl', $tgl)
            ->get()->getRow();
        if ($exist) return $this->fail('Guru sudah melakukan presensi hari ini', 409);

        $db->table('presensi_guru')->insert([
            'guru_id'             => (int)$guruId,
            'tahun_pelajaran_id'  => $tpId,
            'jadwal_id'           => null,
            'tgl'                 => $tgl,
            'jam_dtg'             => $jam ?? date('H:i:s'),
            'status'              => $status,
            'ket'                 => $ket,
            'metode'              => 'manual',
            'crd_at'              => date('Y-m-d H:i:s'),
        ]);
        return $this->respondCreated(['status' => 'success', 'message' => 'Kehadiran berhasil dicatat']);
    }

    public function scan()
    {
        $db     = \Config\Database::connect();
        $userId = $this->request->user_id;
        $guru   = $db->table('mst_guru')->where('user_id', $userId)->get()->getRow();
        if (! $guru) return $this->failNotFound('Data guru tidak ditemukan');

        $today = date('Y-m-d');
        $exist = $db->table('presensi_guru')->where('guru_id', $guru->id)->where('tgl', $today)->get()->getRow();
        if ($exist) return $this->fail('Anda sudah presensi hari ini', 409);

        $activeTp = $db->table('mst_thn_pelajaran')->where('is_aktif', 1)->get()->getRow();
        $tpId = $activeTp ? $activeTp->id : null;

        $db->table('presensi_guru')->insert([
            'guru_id'             => $guru->id,
            'tahun_pelajaran_id'  => $tpId,
            'jadwal_id'           => null,
            'tgl'                 => $today,
            'jam_dtg'             => date('H:i:s'),
            'status'              => 'hadir',
            'ket'                 => 'Scan QR',
            'metode'              => 'qr',
            'crd_at'              => date('Y-m-d H:i:s'),
        ]);
        return $this->respondCreated(['status' => 'success', 'message' => 'Kehadiran berhasil dicatat']);
    }

    // ==================== GLOBAL QR ====================

    public function generateQr()
    {
        $db     = \Config\Database::connect();
        $durasi = (int)($this->request->getVar('durasi') ?? 10);
        $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I,O,0,1 for readability
        $token  = '';
        for ($i = 0; $i < 6; $i++) {
            $token .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $exp    = date('Y-m-d H:i:s', strtotime("+{$durasi} minutes"));

        $this->upsertSetting($db, 'qr_token', $token);
        $this->upsertSetting($db, 'qr_exp', $exp);
        $this->upsertSetting($db, 'qr_durasi', $durasi);

        return $this->respond(['status' => 'success', 'data' => ['token' => $token, 'exp' => $exp, 'durasi' => $durasi]]);
    }

    public function qrStatus()
    {
        $db     = \Config\Database::connect();
        $token  = $db->table('pengaturan')->where('kunci', 'qr_token')->get()->getRow();
        $exp    = $db->table('pengaturan')->where('kunci', 'qr_exp')->get()->getRow();
        $durasi = $db->table('pengaturan')->where('kunci', 'qr_durasi')->get()->getRow();

        return $this->respond(['status' => 'success', 'data' => [
            'token'  => $token ? $token->nilai : null,
            'exp'    => $exp ? $exp->nilai : null,
            'durasi' => $durasi ? (int)$durasi->nilai : 10,
        ]]);
    }

    public function verifyQr($token = null)
    {
        $db     = \Config\Database::connect();
        $stored = $db->table('pengaturan')->where('kunci', 'qr_token')->get()->getRow();
        $expRow = $db->table('pengaturan')->where('kunci', 'qr_exp')->get()->getRow();

        if (! $stored || $stored->nilai !== $token) return $this->fail('QR Code tidak valid', 404);
        if ($expRow && $expRow->nilai < date('Y-m-d H:i:s')) return $this->fail('QR Code sudah kadaluarsa', 404);

        return $this->respond(['status' => 'success', 'message' => 'QR Code valid']);
    }

    private function upsertSetting($db, $kunci, $nilai)
    {
        $exist = $db->table('pengaturan')->where('kunci', $kunci)->get()->getRow();
        if ($exist) {
            $db->table('pengaturan')->where('kunci', $kunci)->update(['nilai' => (string)$nilai, 'upd_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('pengaturan')->insert(['kunci' => $kunci, 'nilai' => (string)$nilai, 'crd_at' => date('Y-m-d H:i:s'), 'upd_at' => date('Y-m-d H:i:s')]);
        }
    }
}