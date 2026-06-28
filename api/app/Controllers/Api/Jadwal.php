<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\JadwalModel;
use App\Models\KelasModel;
use CodeIgniter\API\ResponseTrait;

class Jadwal extends BaseController
{
    use ResponseTrait;

    protected $model;
    public function __construct() { $this->model = new JadwalModel(); }

    public function index()
    {
        $tpId = $this->request->getGet('tahun_pelajaran_id');
        $guruId = $this->request->getGet('guru_id');
        $kelasId = $this->request->getGet('kelas_id');

        $db = \Config\Database::connect();
        $builder = $db->table('jadwal_mengajar j')
            ->select('j.*, g.nm_lengkap as guru_nama, m.nm_mapel, jam.kode as jam_kode, jam.jam_mulai, jam.jam_selesai, jam.urutan, k.nm_kelas')
            ->join('mst_guru g', 'g.id = j.guru_id', 'left')
            ->join('mst_mapel m', 'm.id = j.mapel_id', 'left')
            ->join('mst_jam jam', 'jam.id = j.jam_id', 'left')
            ->join('mst_kelas k', 'k.id = j.kelas_id', 'left')
            ->where('j.dlt_at', null)
            ->orderBy('j.hari', 'ASC')
            ->orderBy('jam.urutan', 'ASC');

        if ($tpId) $builder->where('j.tahun_pelajaran_id', $tpId);
        if ($guruId) $builder->where('j.guru_id', $guruId);
        if ($kelasId) $builder->where('j.kelas_id', $kelasId);

        return $this->respond(['status' => 'success', 'data' => $builder->get()->getResult()]);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) return $this->failNotFound('Jadwal tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $rules = [
            'tahun_pelajaran_id' => 'required',
            'guru_id'            => 'required',
            'mapel_id'           => 'required',
            'kelas_id'           => 'required',
            'jam_id'             => 'required',
            'hari'               => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu]',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        $data = $this->request->getJSON(true);

        // Bentrok detection
        $conflict = $this->model->where([
            'guru_id'            => $data['guru_id'],
            'hari'               => $data['hari'],
            'jam_id'             => $data['jam_id'],
            'tahun_pelajaran_id' => $data['tahun_pelajaran_id'],
        ])->first();
        if ($conflict) return $this->fail('Guru sudah memiliki jadwal di hari & jam yang sama (bentrok)', 409);

        if ($this->model->insert($data)) {
            return $this->respondCreated([
                'status' => 'success', 'message' => 'Jadwal berhasil ditambahkan',
                'data' => $this->model->find($this->model->getInsertID()),
            ]);
        }
        return $this->failValidationErrors($this->model->errors());
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) return $this->failNotFound('Jadwal tidak ditemukan');
        $data = $this->request->getJSON(true);
        if ($this->model->update($id, $data)) return $this->respond(['status' => 'success', 'message' => 'Jadwal diperbarui']);
        return $this->failValidationErrors($this->model->errors());
    }

    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'success', 'message' => 'Jadwal dihapus']);
    }

    /**
     * GET /api/jadwal/kelas/:kelasId
     * Return all jadwal for a specific class, grouped by hari & jam
     */
    public function byKelas($kelasId = null)
    {
        if (! $kelasId) return $this->fail('kelasId diperlukan', 400);

        $db = \Config\Database::connect();

        // Get kelas info
        $kelas = (new KelasModel())->find($kelasId);
        if (! $kelas) return $this->failNotFound('Kelas tidak ditemukan');

        // Get jadwal for this kelas with joins
        $jadwal = $db->table('jadwal_mengajar j')
            ->select('j.*, g.nm_lengkap as guru_nama, m.nm_mapel, jam.kode as jam_kode, jam.jam_mulai, jam.jam_selesai, jam.urutan')
            ->join('mst_guru g', 'g.id = j.guru_id', 'left')
            ->join('mst_mapel m', 'm.id = j.mapel_id', 'left')
            ->join('mst_jam jam', 'jam.id = j.jam_id', 'left')
            ->where('j.kelas_id', $kelasId)
            ->where('j.dlt_at', null)
            ->orderBy('jam.urutan', 'ASC')
            ->orderBy('j.hari', 'ASC')
            ->get()->getResult();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'kelas'  => $kelas,
                'jadwal' => $jadwal,
            ],
        ]);
    }

    /**
     * POST /api/jadwal/assign
     * Create or update a single assignment (for drag-drop / quick edit)
     */
    public function assign()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'kelas_id'           => 'required',
            'guru_id'            => 'required',
            'mapel_id'           => 'required',
            'jam_id'             => 'required',
            'hari'               => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu]',
            'tahun_pelajaran_id' => 'required',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        // Cek existing
        $existing = $this->model->where([
            'kelas_id'           => $data['kelas_id'],
            'jam_id'             => $data['jam_id'],
            'hari'               => $data['hari'],
            'tahun_pelajaran_id' => $data['tahun_pelajaran_id'],
        ])->first();

        if ($existing) {
            // Update existing
            $this->model->update($existing->id, [
                'guru_id'  => $data['guru_id'],
                'mapel_id' => $data['mapel_id'],
            ]);
            return $this->respond(['status' => 'success', 'message' => 'Jadwal diperbarui', 'data' => $this->model->find($existing->id)]);
        }

        // Create new — check bentrok guru
        $conflict = $this->model->where([
            'guru_id'            => $data['guru_id'],
            'hari'               => $data['hari'],
            'jam_id'             => $data['jam_id'],
            'tahun_pelajaran_id' => $data['tahun_pelajaran_id'],
        ])->first();
        if ($conflict) return $this->fail('Guru sudah memiliki jadwal di hari & jam yang sama', 409);

        if ($this->model->insert($data)) {
            return $this->respondCreated([
                'status' => 'success', 'message' => 'Jadwal ditambahkan',
                'data'   => $this->model->find($this->model->getInsertID()),
            ]);
        }
        return $this->failValidationErrors($this->model->errors());
    }

    public function hariIni()
    {
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][date('w')];
        $data = $this->model->where('hari', $hari)->findAll();
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function byGuru($guruId = null)
    {
        $data = $this->model->where('guru_id', $guruId)->orderBy('hari', 'ASC')->orderBy('jam_id', 'ASC')->findAll();
        return $this->respond(['status' => 'success', 'data' => $data]);
    }
}