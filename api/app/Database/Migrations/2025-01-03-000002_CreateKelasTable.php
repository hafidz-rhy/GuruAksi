<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKelasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'kode_kelas'         => ['type' => 'VARCHAR', 'constraint' => 10],
            'nm_kelas'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'tingkat'            => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'jurusan'            => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'tahun_pelajaran_id' => ['type' => 'INT', 'constraint' => 11],
            'wali_kelas_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'crd_at'             => ['type' => 'DATETIME', 'null' => true],
            'upd_at'             => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tahun_pelajaran_id', 'mst_thn_pelajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('wali_kelas_id', 'mst_guru', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('mst_kelas');
    }

    public function down()
    {
        $this->forge->dropTable('mst_kelas');
    }
}