<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PenugasanModel;
use CodeIgniter\API\ResponseTrait;

class Penugasan extends BaseController
{
    use ResponseTrait;

    protected $model;
    public function __construct() { $this->model = new PenugasanModel(); }

    /**
     * GET /api/penugasan
     * Query params: tahun_pelajaran_id, guru_id, mapel_id
     */
    public function index()
    {
        $tpId   = $this->request->getGet('tahun_pelajaran_id');
        $guruId = $this->request->getGet('guru_id');
        $mapelId = $this->request->getGet('mapel_id');

        $db = \Config\Database::connect();
        $builder = $db->table('penugasan_guru p')
            ->select('p.*, g.nm_lengkap as guru_nama, m.kode as mapel_kode, m.nm_mapel')
            ->join('mst_guru g', 'g.id = p.guru_id', 'left')
            ->join('mst_mapel m', 'm.id = p.mapel_id', 'left')
            ->where('p.dlt_at', null)
            ->orderBy('g.nm_lengkap', 'ASC')
            ->orderBy('m.nm_mapel', 'ASC');

        if ($tpId) $builder->where('p.tahun_pelajaran_id', $tpId);
        if ($guruId) $builder->where('p.guru_id', $guruId);
        if ($mapelId) $builder->where('p.mapel_id', $mapelId);

        return $this->respond(['status' => 'success', 'data' => $builder->get()->getResult()]);
    }

    /**
     * GET /api/penugasan/mapel-oleh-guru/:guruId
     * Returns mapel list that is assigned to a specific guru
     */
    public function mapelOlehGuru($guruId = null)
    {
        if (!$guruId) return $this->fail('guru_id diperlukan', 400);

        $tpId = $this->request->getGet('tahun_pelajaran_id');

        $db = \Config\Database::connect();
        $builder = $db->table('penugasan_guru p')
            ->select('p.mapel_id, m.kode, m.nm_mapel')
            ->join('mst_mapel m', 'm.id = p.mapel_id', 'inner')
            ->where('p.guru_id', $guruId)
            ->where('p.status', 'aktif')
            ->where('p.dlt_at', null)
            ->where('m.dlt_at', null)
            ->groupBy('p.mapel_id')
            ->orderBy('m.nm_mapel', 'ASC');

        if ($tpId) $builder->where('p.tahun_pelajaran_id', $tpId);

        return $this->respond(['status' => 'success', 'data' => $builder->get()->getResult()]);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (!$data) return $this->failNotFound('Penugasan tidak ditemukan');
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $rules = [
            'tahun_pelajaran_id' => 'required',
            'guru_id'            => 'required',
            'mapel_id'           => 'required',
        ];
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        // Cek duplikat
        $exists = $this->model->where([
            'tahun_pelajaran_id' => $data['tahun_pelajaran_id'],
            'guru_id'            => $data['guru_id'],
            'mapel_id'           => $data['mapel_id'],
            'dlt_at'             => null,
        ])->first();

        if ($exists) {
            return $this->fail('Penugasan untuk guru dan mapel ini sudah ada di tahun tersebut', 409);
        }

        $data['status'] = $data['status'] ?? 'aktif';

        if ($this->model->insert($data)) {
            return $this->respondCreated([
                'status'  => 'success',
                'message' => 'Penugasan berhasil ditambahkan',
                'data'    => $this->model->find($this->model->getInsertID()),
            ]);
        }
        return $this->failValidationErrors($this->model->errors());
    }

    public function update($id = null)
    {
        if (!$this->model->find($id)) return $this->failNotFound('Penugasan tidak ditemukan');
        $data = $this->request->getJSON(true);
        if ($this->model->update($id, $data)) {
            return $this->respond(['status' => 'success', 'message' => 'Penugasan diperbarui']);
        }
        return $this->failValidationErrors($this->model->errors());
    }

    public function delete($id = null)
    {
        $this->model->delete($id);
        return $this->respondDeleted(['status' => 'success', 'message' => 'Penugasan dihapus']);
    }

    /**
     * POST /api/penugasan/batch
     * Batch assign multiple mapel to a guru in one year
     * Body: { tahun_pelajaran_id, guru_id, mapel_ids: [1,2,3] }
     */
    public function batch()
    {
        $data = $this->request->getJSON(true);
        $tpId   = $data['tahun_pelajaran_id'] ?? null;
        $guruId = $data['guru_id'] ?? null;
        $mapelIds = $data['mapel_ids'] ?? [];

        if (!$tpId || !$guruId) {
            return $this->fail('tahun_pelajaran_id dan guru_id wajib diisi', 400);
        }

        $db = \Config\Database::connect();
        $inserted = 0;

        // Hapus dulu penugasan lama untuk guru+tp ini
        $this->model->where('tahun_pelajaran_id', $tpId)
            ->where('guru_id', $guruId)
            ->delete();

        // Insert baru
        foreach ($mapelIds as $mapelId) {
            if ($this->model->insert([
                'tahun_pelajaran_id' => $tpId,
                'guru_id'            => $guruId,
                'mapel_id'           => $mapelId,
                'status'             => 'aktif',
            ], false)) {
                $inserted++;
            }
        }

        return $this->respond([
            'status'  => 'success',
            'message' => "Berhasil menyimpan $inserted penugasan",
            'data'    => ['inserted' => $inserted],
        ]);
    }
}