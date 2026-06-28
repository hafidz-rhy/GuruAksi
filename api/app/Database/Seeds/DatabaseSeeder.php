<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Insert tahun pelajaran default
        $this->db->table('mst_thn_pelajaran')->insert([
            'nama'          => '2025/2026',
            'tgl_mulai'     => '2025-07-14',
            'tgl_berakhir'  => '2026-06-30',
            'is_aktif'      => true,
            'crd_at'        => date('Y-m-d H:i:s'),
            'upd_at'        => date('Y-m-d H:i:s'),
        ]);

        // Insert admin user
        $this->db->table('users')->insert([
            'username'  => 'admin',
            'pwd'       => password_hash('admin123', PASSWORD_BCRYPT),
            'role'      => 'admin',
            'status'    => 'aktif',
            'crd_at'    => date('Y-m-d H:i:s'),
            'upd_at'    => date('Y-m-d H:i:s'),
        ]);

        // Insert kamad user
        $this->db->table('users')->insert([
            'username'  => 'kamad',
            'pwd'       => password_hash('kamad123', PASSWORD_BCRYPT),
            'role'      => 'kamad',
            'status'    => 'aktif',
            'crd_at'    => date('Y-m-d H:i:s'),
            'upd_at'    => date('Y-m-d H:i:s'),
        ]);

        // Insert guru user
        $this->db->table('users')->insert([
            'username'  => 'guru1',
            'pwd'       => password_hash('guru123', PASSWORD_BCRYPT),
            'role'      => 'guru',
            'status'    => 'aktif',
            'crd_at'    => date('Y-m-d H:i:s'),
            'upd_at'    => date('Y-m-d H:i:s'),
        ]);

        // Insert guru data
        $this->db->table('mst_guru')->insert([
            'user_id'    => 2,
            'peg_id'     => 'PEG001',
            'nik'        => '198501012010011001',
            'nm_lengkap' => 'Prof. Dr. Hafidz Luqman',
            'nm_panggil' => 'Hafidz',
            'mapel_id'   => null,
            'tmp_lahir'  => 'Jember',
            'tgl_lahir'  => '1985-01-01',
            'kelamin'    => 'L',
            'agama'      => 'Islam',
            'alamat'     => 'Jl. Sultan Agung 18 Balungkulon, Balung, Jember, Jawa Timur',
            'no_telp'    => '082334641420',
            'email'      => 'cs.aitiservices@gmail.com',
            'status'     => 'aktif',
            'crd_at'     => date('Y-m-d H:i:s'),
            'upd_at'     => date('Y-m-d H:i:s'),
        ]);
        // Insert guru data
        $this->db->table('mst_guru')->insert([
            'user_id'    => 3,
            'peg_id'     => 'PEG002',
            'nik'        => '199001012010011001',
            'nm_lengkap' => 'Akhmad Hafiedz, S.Pd',
            'nm_panggil' => 'Akhmad',
            'mapel_id'   => null,
            'tmp_lahir'  => 'Jember',
            'tgl_lahir'  => '1990-01-01',
            'kelamin'    => 'L',
            'agama'      => 'Islam',
            'alamat'     => 'Jl. Teuku Umar Ambulu',
            'no_telp'    => '082334641420',
            'email'      => 'cs2.aitiservices@gmail.com',
            'status'     => 'aktif',
            'crd_at'     => date('Y-m-d H:i:s'),
            'upd_at'     => date('Y-m-d H:i:s'),
        ]);

        // Insert pengaturan default
        $this->db->table('pengaturan')->insert([
            'kunci' => 'recaptcha_site_key',
            'nilai' => json_encode(['site_key' => '', 'is_active' => false]),
            'crd_at' => date('Y-m-d H:i:s'),
            'upd_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('pengaturan')->insert([
            'kunci' => 'recaptcha_secret_key',
            'nilai' => '',
            'crd_at' => date('Y-m-d H:i:s'),
            'upd_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('pengaturan')->insert([
            'kunci' => 'nama_sekolah',
            'nilai' => 'MTs Aiti School Universe',
            'crd_at' => date('Y-m-d H:i:s'),
            'upd_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('pengaturan')->insert([
            'kunci' => 'jenjang_sekolah',
            'nilai' => 'SMP/MTs',
            'crd_at' => date('Y-m-d H:i:s'),
            'upd_at' => date('Y-m-d H:i:s'),
        ]);
    }
}