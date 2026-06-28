<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiswaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nisn'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'nis'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nm_lengkap'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'nm_panggil'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'kelas_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'tmp_lahir'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tgl_lahir'         => ['type' => 'DATE', 'null' => true],
            'kelamin'           => ['type' => 'ENUM', 'constraint' => ['L', 'P'], 'null' => true],
            'agama'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alamat'            => ['type' => 'TEXT', 'null' => true],
            'no_telp'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif', 'alumni'], 'default' => 'aktif'],
            'foto'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'crd_at'            => ['type' => 'DATETIME', 'null' => true],
            'upd_at'            => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('kelas_id', 'mst_kelas', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('mst_siswa');
    }

    public function down()
    {
        $this->forge->dropTable('mst_siswa');
    }
}