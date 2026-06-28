<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresensiSiswaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'jurnal_id'     => ['type' => 'INT', 'constraint' => 11],
            'siswa_id'      => ['type' => 'INT', 'constraint' => 11],
            'status'        => ['type' => 'ENUM', 'constraint' => ['hadir', 'izin', 'sakit', 'alpha', 'tanpa_ket'], 'default' => 'hadir'],
            'crd_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('jurnal_id', 'jurnal_mengajar', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('siswa_id', 'mst_siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('presensi_siswa');
    }

    public function down()
    {
        $this->forge->dropTable('presensi_siswa');
    }
}