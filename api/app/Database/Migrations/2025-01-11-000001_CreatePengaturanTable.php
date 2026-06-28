<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengaturanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'kunci'         => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'nilai'         => ['type' => 'TEXT', 'null' => true],
            'crd_at'        => ['type' => 'DATETIME', 'null' => true],
            'upd_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pengaturan');

        $seeder = \Config\Database::seeder();
        $seeder->call('DatabaseSeeder');
    }

    public function down()
    {
        $this->forge->dropTable('pengaturan');
    }
}