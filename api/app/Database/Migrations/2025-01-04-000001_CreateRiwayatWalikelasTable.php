<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRiwayatWalikelasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'guru_id'           => ['type' => 'INT', 'constraint' => 11],
            'thn_pelajaran_id'  => ['type' => 'INT', 'constraint' => 11],
            'kelas'             => ['type' => 'VARCHAR', 'constraint' => 10],
            'jurusan'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tgl_angkat'        => ['type' => 'DATE', 'null' => true],
            'tgl_berakhir'      => ['type' => 'DATE', 'null' => true],
            'is_aktif'          => ['type' => 'BOOLEAN', 'default' => true],
            'crd_at'            => ['type' => 'DATETIME', 'null' => true],
            'upd_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('guru_id', 'mst_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('thn_pelajaran_id', 'mst_thn_pelajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('riwayat_walikelas');
    }

    public function down()
    {
        $this->forge->dropTable('riwayat_walikelas');
    }
}