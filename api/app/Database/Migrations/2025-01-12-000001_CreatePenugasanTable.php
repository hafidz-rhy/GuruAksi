<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePenugasanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tahun_pelajaran_id' => ['type' => 'INT', 'constraint' => 11],
            'guru_id'            => ['type' => 'INT', 'constraint' => 11],
            'mapel_id'           => ['type' => 'INT', 'constraint' => 11],
            'status'             => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'crd_at'             => ['type' => 'DATETIME', 'null' => true],
            'upd_at'             => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tahun_pelajaran_id', 'mst_thn_pelajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'mst_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('mapel_id', 'mst_mapel', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('penugasan_guru');
    }

    public function down()
    {
        $this->forge->dropTable('penugasan_guru');
    }
}