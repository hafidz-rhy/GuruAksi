<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGuruTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'peg_id'        => ['type' => 'VARCHAR', 'constraint' => 20, 'unique' => true],
            'nik'           => ['type' => 'VARCHAR', 'constraint' => 30],
            'nm_lengkap'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'nm_panggil'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'mapel_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'tmp_lahir'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tgl_lahir'     => ['type' => 'DATE', 'null' => true],
            'kelamin'       => ['type' => 'ENUM', 'constraint' => ['L', 'P'], 'null' => true],
            'agama'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alamat'        => ['type' => 'TEXT', 'null' => true],
            'no_telp'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif', 'cuti'], 'default' => 'aktif'],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'crd_at'        => ['type' => 'DATETIME', 'null' => true],
            'upd_at'        => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('mapel_id', 'mst_mapel', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('mst_guru');
    }

    public function down()
    {
        $this->forge->dropTable('mst_guru');
    }
}