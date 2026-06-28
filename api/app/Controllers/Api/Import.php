<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\GuruModel;
use App\Models\SiswaModel;
use CodeIgniter\API\ResponseTrait;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Import extends BaseController
{
    use ResponseTrait;

    /**
     * POST /api/import/siswa
     * Upload Excel siswa. All-or-nothing: reject all if any duplicate NIS/NISN.
     */
    public function siswa()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->fail('File tidak valid', 400);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->fail('Format file harus Excel (.xlsx, .xls, .csv)', 400);
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return $this->fail('Gagal membaca file Excel: ' . $e->getMessage(), 400);
        }

        if (count($rows) < 2) {
            return $this->fail('File Excel kosong atau hanya berisi header', 400);
        }

        // Header row
        $headers = array_map('strtolower', array_map('trim', $rows[0]));
        $dataRows = array_slice($rows, 1);

        // Map column indexes
        $map = $this->mapHeadersSiswa($headers);
        if (!$map) {
            return $this->fail('Header kolom tidak dikenali. Pastikan ada: nis, nisn, nm_lengkap, kelas', 400);
        }

        // Phase 1: Validate ALL rows first
        $duplicates = [];
        $validRows = [];
        $model = new SiswaModel();
        $db = Database::connect();

        foreach ($dataRows as $idx => $row) {
            $line = $idx + 2; // Excel line number
            $nis = trim($row[$map['nis']] ?? '');
            $nisn = trim($row[$map['nisn']] ?? '');
            $nama = trim($row[$map['nm_lengkap']] ?? '');
            $kelas = trim($row[$map['kelas']] ?? '');
            $jk = trim($row[$map['kelamin'] ?? -1] ?? '');
            $alamat = trim($row[$map['alamat'] ?? -1] ?? '');
            $no_telp = trim($row[$map['no_telp'] ?? -1] ?? '');
            $nama_ayah = trim($row[$map['nama_ayah'] ?? -1] ?? '');
            $nama_ibu = trim($row[$map['nama_ibu'] ?? -1] ?? '');
            $thn_masuk = trim($row[$map['thn_masuk'] ?? -1] ?? '');

            if (empty($nis) && empty($nisn)) {
                $duplicates[] = ['line' => $line, 'nis' => $nis, 'nisn' => $nisn, 'nama' => $nama, 'reason' => 'NIS dan NISN kosong'];
                continue;
            }

            // Check duplicate in DB
            $dupNis = null;
            $dupNisn = null;
            if (!empty($nis)) {
                $dupNis = $model->where('nis', $nis)->where('dlt_at', null)->first();
            }
            if (!empty($nisn)) {
                $dupNisn = $model->where('nisn', $nisn)->where('dlt_at', null)->first();
            }

            $reasons = [];
            if ($dupNis) $reasons[] = "NIS '$nis' sudah ada (ID: {$dupNis->id}, Nama: {$dupNis->nm_lengkap})";
            if ($dupNisn) $reasons[] = "NISN '$nisn' sudah ada (ID: {$dupNisn->id}, Nama: {$dupNisn->nm_lengkap})";

            if ($reasons) {
                $duplicates[] = ['line' => $line, 'nis' => $nis, 'nisn' => $nisn, 'nama' => $nama, 'reason' => implode('; ', $reasons)];
                continue;
            }

            // Check duplicate within batch
            foreach ($validRows as $vr) {
                if (!empty($nis) && $vr['nis'] === $nis) {
                    $duplicates[] = ['line' => $line, 'nis' => $nis, 'nisn' => $nisn, 'nama' => $nama, 'reason' => "NIS '$nis' duplikat dalam file (baris {$vr['line']})"];
                    continue 2;
                }
                if (!empty($nisn) && $vr['nisn'] === $nisn) {
                    $duplicates[] = ['line' => $line, 'nis' => $nis, 'nisn' => $nisn, 'nama' => $nama, 'reason' => "NISN '$nisn' duplikat dalam file (baris {$vr['line']})"];
                    continue 2;
                }
            }

            $validRows[] = [
                'line'       => $line,
                'nis'        => $nis,
                'nisn'       => $nisn,
                'nama'       => $nama,
                'kelas'      => $kelas,
                'kelamin'    => in_array(strtoupper($jk), ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN']) ? (in_array(strtoupper($jk), ['L', 'LAKI-LAKI']) ? 'L' : 'P') : 'L',
                'alamat'     => $alamat,
                'no_telp'    => $no_telp,
                'nama_ayah'  => $nama_ayah,
                'nama_ibu'   => $nama_ibu,
                'thn_masuk'  => $thn_masuk,
            ];
        }

        // If any duplicates found, reject ALL
        if (count($duplicates) > 0) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Ditemukan ' . count($duplicates) . ' data duplikat/ganda. Seluruh data ditolak.',
                'data' => [
                    'total_rows'    => count($dataRows),
                    'valid_rows'    => count($validRows),
                    'duplicate_rows'=> count($duplicates),
                    'duplicates'    => $duplicates,
                ],
            ], 422);
        }

        // Phase 2: Insert all
        $inserted = 0;
        foreach ($validRows as $vr) {
            try {
                $model->insert([
                    'nis'        => $vr['nis'],
                    'nisn'       => $vr['nisn'],
                    'nm_lengkap' => $vr['nama'],
                    'kelas'      => $vr['kelas'],
                    'kelamin'    => $vr['kelamin'],
                    'alamat'     => $vr['alamat'],
                    'no_telp'    => $vr['no_telp'],
                    'nama_ayah'  => $vr['nama_ayah'],
                    'nama_ibu'   => $vr['nama_ibu'],
                    'thn_masuk'  => $vr['thn_masuk'],
                    'status'     => 'aktif',
                ], false);
                $inserted++;
            } catch (\Throwable $e) {
                log_message('error', 'Import siswa insert failed: ' . $e->getMessage());
            }
        }

        return $this->respond([
            'status'  => 'success',
            'message' => "Berhasil import $inserted data siswa",
            'data'    => ['inserted' => $inserted, 'total' => count($validRows)],
        ]);
    }

    /**
     * POST /api/import/guru
     * Upload Excel guru + create user account. All-or-nothing: reject all if any duplicate NIK.
     */
    public function guru()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->fail('File tidak valid', 400);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->fail('Format file harus Excel (.xlsx, .xls, .csv)', 400);
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return $this->fail('Gagal membaca file Excel: ' . $e->getMessage(), 400);
        }

        if (count($rows) < 2) {
            return $this->fail('File Excel kosong atau hanya berisi header', 400);
        }

        $headers = array_map('strtolower', array_map('trim', $rows[0]));
        $dataRows = array_slice($rows, 1);

        $map = $this->mapHeadersGuru($headers);
        if (!$map) {
            return $this->fail('Header kolom tidak dikenali. Pastikan ada: peg_id, nik, nm_lengkap', 400);
        }

        // Phase 1: Validate ALL rows
        $duplicates = [];
        $validRows = [];
        $model = new GuruModel();
        $db = Database::connect();

        foreach ($dataRows as $idx => $row) {
            $line = $idx + 2;
            $pegId = trim($row[$map['peg_id']] ?? '');
            $nik = trim($row[$map['nik']] ?? '');
            $nama = trim($row[$map['nm_lengkap']] ?? '');
            $email = trim($row[$map['email'] ?? -1] ?? '');
            $no_telp = trim($row[$map['no_telp'] ?? -1] ?? '');
            $alamat = trim($row[$map['alamat'] ?? -1] ?? '');
            $kelamin = trim($row[$map['kelamin'] ?? -1] ?? '');
            $tmp_lahir = trim($row[$map['tmp_lahir'] ?? -1] ?? '');
            $tgl_lahir = trim($row[$map['tgl_lahir'] ?? -1] ?? '');

            if (empty($nik)) {
                $duplicates[] = ['line' => $line, 'nik' => $nik, 'nama' => $nama, 'reason' => 'NIK kosong'];
                continue;
            }

            // Check duplicate NIK in DB
            $dupNik = $model->where('nik', $nik)->where('dlt_at', null)->first();
            if ($dupNik) {
                $duplicates[] = ['line' => $line, 'nik' => $nik, 'nama' => $nama, 'reason' => "NIK '$nik' sudah ada (ID: {$dupNik->id}, Nama: {$dupNik->nm_lengkap})"];
                continue;
            }

            // Check duplicate NIK within batch
            foreach ($validRows as $vr) {
                if ($vr['nik'] === $nik) {
                    $duplicates[] = ['line' => $line, 'nik' => $nik, 'nama' => $nama, 'reason' => "NIK '$nik' duplikat dalam file (baris {$vr['line']})"];
                    continue 2;
                }
            }

            // Check duplicate peg_id in DB if provided
            if (!empty($pegId)) {
                $dupPeg = $model->where('peg_id', $pegId)->where('dlt_at', null)->first();
                if ($dupPeg) {
                    $duplicates[] = ['line' => $line, 'nik' => $nik, 'nama' => $nama, 'reason' => "Peg ID '$pegId' sudah ada (ID: {$dupPeg->id}, Nama: {$dupPeg->nm_lengkap})"];
                    continue;
                }
            }

            $validRows[] = [
                'line'       => $line,
                'peg_id'     => $pegId,
                'nik'        => $nik,
                'nama'       => $nama,
                'email'      => $email,
                'no_telp'    => $no_telp,
                'alamat'     => $alamat,
                'kelamin'    => in_array(strtoupper($kelamin), ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN']) ? (in_array(strtoupper($kelamin), ['L', 'LAKI-LAKI']) ? 'L' : 'P') : 'L',
                'tmp_lahir'  => $tmp_lahir,
                'tgl_lahir'  => $tgl_lahir,
            ];
        }

        if (count($duplicates) > 0) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Ditemukan ' . count($duplicates) . ' data duplikat/ganda. Seluruh data ditolak.',
                'data' => [
                    'total_rows'    => count($dataRows),
                    'valid_rows'    => count($validRows),
                    'duplicate_rows'=> count($duplicates),
                    'duplicates'    => $duplicates,
                ],
            ], 422);
        }

        // Phase 2: Insert guru + create user account
        $inserted = 0;
        foreach ($validRows as $vr) {
            $db->transBegin();
            try {
                // Generate username from peg_id or email prefix
                $username = !empty($vr['peg_id']) ? $vr['peg_id'] : explode('@', $vr['email'] ?? 'guru')[0];
                $username = preg_replace('/[^a-zA-Z0-9]/', '', $username);

                // Ensure unique username
                $baseUsername = $username;
                $counter = 1;
                while ($db->table('users')->where('username', $username)->countAllResults() > 0) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Create user
                $db->table('users')->insert([
                    'username' => strtolower($username),
                    'password' => password_hash($username . '123', PASSWORD_DEFAULT),
                    'role'     => 'guru',
                    'status'   => 'aktif',
                    'crd_at'   => date('Y-m-d H:i:s'),
                    'upd_at'   => date('Y-m-d H:i:s'),
                ]);
                $userId = $db->insertID();

                // Insert guru
                $model->insert([
                    'user_id'   => $userId,
                    'peg_id'    => $vr['peg_id'] ?: null,
                    'nik'       => $vr['nik'],
                    'nm_lengkap'=> $vr['nama'],
                    'email'     => $vr['email'] ?: null,
                    'no_telp'   => $vr['no_telp'] ?: '',
                    'alamat'    => $vr['alamat'] ?: '',
                    'kelamin'   => $vr['kelamin'],
                    'tmp_lahir' => $vr['tmp_lahir'] ?: null,
                    'tgl_lahir' => $vr['tgl_lahir'] ?: null,
                    'status'    => 'aktif',
                ], false);

                $db->transCommit();
                $inserted++;
            } catch (\Throwable $e) {
                $db->transRollback();
                $duplicates[] = ['line' => $vr['line'], 'nik' => $vr['nik'], 'nama' => $vr['nama'], 'reason' => 'Error insert: ' . $e->getMessage()];
                log_message('error', 'Import guru insert failed (line ' . $vr['line'] . '): ' . $e->getMessage());
            }
        }

        return $this->respond([
            'status'  => 'success',
            'message' => "Berhasil import $inserted data guru (beserta akun)",
            'data'    => [
                'inserted'       => $inserted,
                'total'          => count($validRows),
                'failed_inserts' => count($duplicates) > 0 ? $duplicates : [],
            ],
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function mapHeadersSiswa(array $headers)
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $h = str_replace([' ', '_', '-'], '', $h);
            switch (true) {
                case in_array($h, ['nis', 'noinduk', 'nomorinduk', 'nisn2']): $map['nis'] = $i; break;
                case in_array($h, ['nisn', 'nomorinduknasional']): $map['nisn'] = $i; break;
                case in_array($h, ['nama', 'namalengkap', 'nm_lengkap', 'namasiswa', 'namalengkap']): $map['nm_lengkap'] = $i; break;
                case in_array($h, ['kelas', 'kelass', 'namakelas', 'nm_kelas', 'ruang']): $map['kelas'] = $i; break;
                case in_array($h, ['kelamin', 'jk', 'jeniskelamin', 'gender', 'sex']): $map['kelamin'] = $i; break;
                case in_array($h, ['alamat', 'alamatrumah']): $map['alamat'] = $i; break;
                case $h === 'notelp' || $h === 'notelepon' || $h === 'telp' || $h === 'hp': $map['no_telp'] = $i; break;
                case in_array($h, ['namaayah', 'ayah', 'nm_ayah', 'bapak']): $map['nama_ayah'] = $i; break;
                case in_array($h, ['namaibu', 'ibu', 'nm_ibu']): $map['nama_ibu'] = $i; break;
                case in_array($h, ['thnmasuk', 'tahunmasuk', 'angkatan']): $map['thn_masuk'] = $i; break;
            }
        }
        return (isset($map['nis']) || isset($map['nisn'])) && isset($map['nm_lengkap']) && isset($map['kelas']) ? $map : null;
    }

    private function mapHeadersGuru(array $headers)
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $h = str_replace([' ', '_', '-'], '', $h);
            switch (true) {
                case in_array($h, ['pegid', 'peg_id', 'idpegawai', 'nip', 'nuptk']): $map['peg_id'] = $i; break;
                case in_array($h, ['nik', 'noktp', 'ktp', 'noktp']): $map['nik'] = $i; break;
                case in_array($h, ['nama', 'namalengkap', 'nm_lengkap', 'namaguru']): $map['nm_lengkap'] = $i; break;
                case in_array($h, ['email', 'surel', 'mail']): $map['email'] = $i; break;
                case $h === 'notelp' || $h === 'notelepon' || $h === 'telp' || $h === 'hp': $map['no_telp'] = $i; break;
                case in_array($h, ['alamat', 'alamatrumah']): $map['alamat'] = $i; break;
                case in_array($h, ['kelamin', 'jk', 'jeniskelamin', 'gender', 'sex']): $map['kelamin'] = $i; break;
                case in_array($h, ['tmplahir', 'tempatlahir', 'kotalahir']): $map['tmp_lahir'] = $i; break;
                case in_array($h, ['tgllahir', 'tanggallahir', 'lahir', 'dob']): $map['tgl_lahir'] = $i; break;
            }
        }
        return isset($map['nik']) && isset($map['nm_lengkap']) ? $map : null;
    }
}