<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMapelTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'kode'     => ['type' => 'VARCHAR', 'constraint' => 10, 'unique' => true],
            'nm_mapel' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'   => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'crd_at'   => ['type' => 'DATETIME', 'null' => true],
            'upd_at'   => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('mst_mapel');

        // Seed default data
        $seeder = \Config\Database::seeder();
        $seeder->call('MapelSeeder');
    }

    public function down()
    {
        $this->forge->dropTable('mst_mapel');
    }
}