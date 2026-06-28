<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJamTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'kode'        => ['type' => 'VARCHAR', 'constraint' => 5],
            'jam_mulai'   => ['type' => 'TIME'],
            'jam_selesai' => ['type' => 'TIME'],
            'jenis'       => ['type' => 'ENUM', 'constraint' => ['pelajaran', 'istirahat'], 'default' => 'pelajaran'],
            'urutan'      => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'crd_at'      => ['type' => 'DATETIME', 'null' => true],
            'upd_at'      => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('mst_jam');
    }

    public function down()
    {
        $this->forge->dropTable('mst_jam');
    }
}