<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThnPelajaranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'tgl_mulai'     => ['type' => 'DATE'],
            'tgl_berakhir'  => ['type' => 'DATE'],
            'is_aktif'      => ['type' => 'BOOLEAN', 'default' => false],
            'crd_at'        => ['type' => 'DATETIME', 'null' => true],
            'upd_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('mst_thn_pelajaran');
    }

    public function down()
    {
        $this->forge->dropTable('mst_thn_pelajaran');
    }
}