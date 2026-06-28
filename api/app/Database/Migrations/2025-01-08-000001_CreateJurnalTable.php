<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJurnalTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tahun_pelajaran_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'jadwal_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'guru_id'             => ['type' => 'INT', 'constraint' => 11],
            'kelas_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'mapel_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'tgl'                 => ['type' => 'DATE'],
            'materi'              => ['type' => 'TEXT', 'null' => true],
            'catatan'             => ['type' => 'TEXT', 'null' => true],
            'siswa_tidak_hadir'   => ['type' => 'TEXT', 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['terisi', 'belum'], 'default' => 'terisi'],
            'crd_at'              => ['type' => 'DATETIME', 'null' => true],
            'upd_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tahun_pelajaran_id', 'mst_thn_pelajaran', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('jadwal_id', 'jadwal_mengajar', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'mst_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kelas_id', 'mst_kelas', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('mapel_id', 'mst_mapel', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('jurnal_mengajar');
    }

    public function down()
    {
        $this->forge->dropTable('jurnal_mengajar');
    }
}