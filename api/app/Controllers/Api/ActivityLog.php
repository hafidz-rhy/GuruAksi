<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;
use CodeIgniter\API\ResponseTrait;

class ActivityLog extends BaseController
{
    use ResponseTrait;

    protected $model;
    public function __construct() { $this->model = new ActivityLogModel(); }

    /**
     * GET /api/activity
     * Query params: user_id, role, action, limit, page
     */
    public function index()
    {
        $userId = $this->request->getGet('user_id');
        $role   = $this->request->getGet('role');
        $action = $this->request->getGet('action');
        $limit  = (int)($this->request->getGet('limit') ?? 50);
        $page   = (int)($this->request->getGet('page') ?? 1);

        $builder = $this->model->orderBy('crd_at', 'DESC');

        if ($userId) $builder->where('user_id', $userId);
        if ($role)   $builder->where('role', $role);
        if ($action) $builder->where('action', $action);

        $total  = $builder->countAllResults(false);
        $data   = $builder->limit($limit, ($page - 1) * $limit)->find();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'items' => $data,
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
            ],
        ]);
    }
}
