<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresensiGuruTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tahun_pelajaran_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'jadwal_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'guru_id'             => ['type' => 'INT', 'constraint' => 11],
            'tgl'                 => ['type' => 'DATE'],
            'jam_dtg'             => ['type' => 'TIME', 'null' => true],
            'jam_plg'             => ['type' => 'TIME', 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['hadir', 'izin', 'sakit', 'alpha']],
            'metode'              => ['type' => 'ENUM', 'constraint' => ['qr', 'manual']],
            'ket'                 => ['type' => 'TEXT', 'null' => true],
            'crd_at'              => ['type' => 'DATETIME', 'null' => true],
            'upd_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tahun_pelajaran_id', 'mst_thn_pelajaran', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('jadwal_id', 'jadwal_mengajar', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'mst_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('presensi_guru');
    }

    public function down()
    {
        $this->forge->dropTable('presensi_guru');
    }
}