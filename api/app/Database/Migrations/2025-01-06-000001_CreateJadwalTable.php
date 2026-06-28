<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJadwalTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tahun_pelajaran_id'  => ['type' => 'INT', 'constraint' => 11],
            'guru_id'             => ['type' => 'INT', 'constraint' => 11],
            'mapel_id'            => ['type' => 'INT', 'constraint' => 11],
            'kelas_id'            => ['type' => 'INT', 'constraint' => 11],
            'jam_id'              => ['type' => 'INT', 'constraint' => 11],
            'hari'                => ['type' => 'ENUM', 'constraint' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']],
            'status'              => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'crd_at'              => ['type' => 'DATETIME', 'null' => true],
            'upd_at'              => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tahun_pelajaran_id', 'mst_thn_pelajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'mst_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('mapel_id', 'mst_mapel', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kelas_id', 'mst_kelas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('jam_id', 'mst_jam', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('jadwal_mengajar');
    }

    public function down()
    {
        $this->forge->dropTable('jadwal_mengajar');
    }
}