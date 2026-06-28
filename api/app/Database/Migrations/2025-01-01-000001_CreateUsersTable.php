<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'pwd'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'          => ['type' => 'ENUM', 'constraint' => ['admin', 'guru', 'kamad']],
            'status'        => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'last_login'    => ['type' => 'DATETIME', 'null' => true],
            'crd_at'        => ['type' => 'DATETIME', 'null' => true],
            'upd_at'        => ['type' => 'DATETIME', 'null' => true],
            'dlt_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}