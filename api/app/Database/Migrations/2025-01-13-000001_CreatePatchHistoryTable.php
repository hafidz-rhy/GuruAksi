<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatchHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'version'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'previous_version' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'file_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_size'        => ['type' => 'BIGINT', 'null' => true],
            'manifest'         => ['type' => 'JSON', 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'validating', 'backup', 'applying', 'success', 'failed', 'rolled_back'], 'default' => 'pending'],
            'error_message'    => ['type' => 'TEXT', 'null' => true],
            'applied_by'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'applied_at'       => ['type' => 'DATETIME', 'null' => true],
            'crd_at'           => ['type' => 'DATETIME', 'null' => true],
            'upd_at'           => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('applied_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('patch_history');
    }

    public function down()
    {
        $this->forge->dropTable('patch_history');
    }
}