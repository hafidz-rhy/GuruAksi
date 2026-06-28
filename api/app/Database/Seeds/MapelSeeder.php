<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MapelSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['kode' => 'MTK', 'nm_mapel' => 'Matematika'],
            ['kode' => 'BIN', 'nm_mapel' => 'Bahasa Indonesia'],
            ['kode' => 'BIG', 'nm_mapel' => 'Bahasa Inggris'],
            ['kode' => 'IPA', 'nm_mapel' => 'Ilmu Pengetahuan Alam'],
            ['kode' => 'IPS', 'nm_mapel' => 'Ilmu Pengetahuan Sosial'],
            ['kode' => 'PKN', 'nm_mapel' => 'Pendidikan Kewarganegaraan'],
            ['kode' => 'PAI', 'nm_mapel' => 'Pendidikan Agama Islam'],
            ['kode' => 'PJO', 'nm_mapel' => 'Pendidikan Jasmani & Olahraga'],
            ['kode' => 'SNB', 'nm_mapel' => 'Seni Budaya'],
            ['kode' => 'TIK', 'nm_mapel' => 'Teknologi Informasi & Komunikasi'],
        ];

        $this->db->table('mst_mapel')->insertBatch($data);
    }
}