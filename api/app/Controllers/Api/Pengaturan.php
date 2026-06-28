<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Pengaturan extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $db = \Config\Database::connect();
        $data = $db->table('pengaturan')->get()->getResult();

        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function show($kunci = null)
    {
        $db = \Config\Database::connect();
        $data = $db->table('pengaturan')->where('kunci', $kunci)->get()->getRow();

        if (! $data) {
            return $this->failNotFound('Pengaturan tidak ditemukan');
        }

        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    public function update()
    {
        $db = \Config\Database::connect();
        $inputs = $this->request->getJSON(true);

        if (isset($inputs[0])) {
            // Batch update
            foreach ($inputs as $item) {
                $exist = $db->table('pengaturan')->where('kunci', $item['kunci'])->get()->getRow();
                if ($exist) {
                    $db->table('pengaturan')->where('kunci', $item['kunci'])->update([
                        'nilai'  => $item['nilai'] ?? '',
                        'upd_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $db->table('pengaturan')->insert([
                        'kunci'  => $item['kunci'],
                        'nilai'  => $item['nilai'] ?? '',
                        'crd_at' => date('Y-m-d H:i:s'),
                        'upd_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        return $this->respond(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan']);
    }

    /**
     * GET /api/public/recaptcha
     * Public endpoint - return reCAPTCHA site key & status (no auth)
     */
    public function recaptchaPublic()
    {
        $db = \Config\Database::connect();
        $siteKeyRow = $db->table('pengaturan')->where('kunci', 'recaptcha_site_key')->get()->getRow();

        $siteKey = '';
        $isActive = false;
        if ($siteKeyRow) {
            $val = json_decode($siteKeyRow->nilai, true);
            $siteKey = $val['site_key'] ?? '';
            $isActive = !empty($val['is_active']);
        }

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'site_key'  => $siteKey,
                'is_active' => $isActive,
            ],
        ]);
    }

    /**
     * GET /api/public/logo
     * Public endpoint - return logo URL (no auth)
     */
    public function logoPublic()
    {
        $db = \Config\Database::connect();
        $data = $db->table('pengaturan')->where('kunci', 'logo_madrasah')->get()->getRow();

        if ($data && !empty($data->nilai)) {
            return $this->respond(['status' => 'success', 'data' => ['url' => $data->nilai]]);
        }

        return $this->respond(['status' => 'success', 'data' => ['url' => null]]);
    }

    /**
     * POST /api/pengaturan/upload-logo
     * Upload logo madrasah, replace existing logo
     */
    public function uploadLogo()
    {
        $file = $this->request->getFile('logo');
        if (!$file || !$file->isValid()) {
            return $this->fail('File logo tidak valid', 400);
        }

        $ext = $file->getClientExtension();
        if (!in_array(strtolower($ext), ['png', 'jpg', 'jpeg', 'svg'])) {
            return $this->fail('Format file harus PNG, JPG, atau SVG', 400);
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file->getSize() > $maxSize) {
            return $this->fail('Ukuran file maksimal 2MB', 400);
        }

        // Save to public uploads
        $uploadDir = FCPATH . 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $newName = 'logo.' . $ext;
        $file->move($uploadDir, $newName, true);

        $url = base_url('uploads/' . $newName);

        // Save to pengaturan
        $db = \Config\Database::connect();
        $exist = $db->table('pengaturan')->where('kunci', 'logo_madrasah')->get()->getRow();
        if ($exist) {
            $db->table('pengaturan')->where('kunci', 'logo_madrasah')->update([
                'nilai'  => $url,
                'upd_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->table('pengaturan')->insert([
                'kunci'  => 'logo_madrasah',
                'nilai'  => $url,
                'crd_at' => date('Y-m-d H:i:s'),
                'upd_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'Logo berhasil diupload',
            'data'    => ['url' => $url],
        ]);
    }
}
