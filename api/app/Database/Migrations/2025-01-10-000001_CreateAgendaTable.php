<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'guru_id'       => ['type' => 'INT', 'constraint' => 11],
            'tgl'           => ['type' => 'DATE'],
            'jam'           => ['type' => 'TIME', 'null' => true],
            'judul'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'isi'           => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'selesai', 'batal'], 'default' => 'pending'],
            'crd_at'        => ['type' => 'DATETIME', 'null' => true],
            'upd_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('guru_id', 'mst_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('agenda_guru');
    }

    public function down()
    {
        $this->forge->dropTable('agenda_guru');
    }
}