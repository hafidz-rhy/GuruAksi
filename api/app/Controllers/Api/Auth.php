<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\GuruModel;
use App\Libraries\JwtLibrary;
use CodeIgniter\API\ResponseTrait;

class Auth extends BaseController
{
    use ResponseTrait;

    protected $usersModel;
    protected $guruModel;

    public function __construct()
    {
        $this->usersModel = new UsersModel();
        $this->guruModel  = new GuruModel();
    }

    public function login()
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'pwd'      => 'required|min_length[6]',
        ];
        if (! $this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());

        // Rate Limiting
        $ip = $this->request->getIPAddress();
        $cacheKey = 'login_attempts_' . str_replace(':', '_', $ip);
        $maxAttempts = 5; $lockMinutes = 5; $windowMinutes = 1;
        $attempts = cache($cacheKey) ?: ['count' => 0, 'first_attempt' => time()];
        if ($attempts['count'] >= $maxAttempts) {
            $timeSinceFirst = time() - $attempts['first_attempt'];
            $windowSeconds = $windowMinutes * 60; $lockSeconds = $lockMinutes * 60;
            if ($timeSinceFirst < $windowSeconds) {
                $retryAfter = $windowSeconds - $timeSinceFirst;
                return service('response')->setStatusCode(429)->setJSON(['status'=>'error','message'=>'Terlalu banyak percobaan login. Coba lagi dalam '.ceil($retryAfter/60).' menit.','retry_after'=>$retryAfter]);
            } elseif ($timeSinceFirst < $lockSeconds) {
                $retryAfter = $lockSeconds - $timeSinceFirst;
                return service('response')->setStatusCode(429)->setJSON(['status'=>'error','message'=>'Terlalu banyak percobaan. Silakan tunggu '.ceil($retryAfter/60).' menit sebelum mencoba lagi.','retry_after'=>$retryAfter]);
            } else {
                $attempts = ['count' => 0, 'first_attempt' => time()];
            }
        }

        // === reCAPTCHA Validation ===
        $recaptchaResponse = $this->request->getVar('recaptcha');
        $db = \Config\Database::connect();
        $recaptchaRow = $db->table('pengaturan')->where('kunci', 'recaptcha_site_key')->get()->getRow();
        $recaptchaEnabled = false;
        $recaptchaSecret = '';
        if ($recaptchaRow) {
            $val = json_decode($recaptchaRow->nilai, true);
            $recaptchaEnabled = !empty($val['is_active']);
        }
        if ($recaptchaEnabled) {
            $secretRow = $db->table('pengaturan')->where('kunci', 'recaptcha_secret_key')->get()->getRow();
            $recaptchaSecret = $secretRow ? $secretRow->nilai : '';
        }

        if ($recaptchaEnabled && !empty($recaptchaSecret)) {
            if (empty($recaptchaResponse)) {
                return $this->fail('reCAPTCHA harus diisi', 400);
            }
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $verifyData = [
                'secret'   => $recaptchaSecret,
                'response' => $recaptchaResponse,
                'remoteip' => $this->request->getIPAddress(),
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verifyUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verifyData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $verifyResult = json_decode(curl_exec($ch), true);
            curl_close($ch);

            // reCAPTCHA v2 checkbox returns no score; v3 returns score
            $isV2 = !isset($verifyResult['score']);
            if (empty($verifyResult['success']) || (!$isV2 && ($verifyResult['score'] ?? 0) < 0.5)) {
                return $this->fail('Verifikasi reCAPTCHA gagal. Silakan coba lagi.', 400);
            }
        }

        $username = $this->request->getVar('username');
        $pwd = $this->request->getVar('pwd');
        $user = $this->usersModel->where('username', $username)->first();

        if (! $user || $user->status !== 'aktif' || ! password_verify($pwd, $user->pwd)) {
            if (time() - $attempts['first_attempt'] > ($windowMinutes * 60)) $attempts = ['count' => 0, 'first_attempt' => time()];
            $attempts['count']++;
            cache()->save($cacheKey, $attempts, ($lockMinutes + 1) * 60);
        }
        if (! $user) return $this->failNotFound('Username tidak ditemukan');
        if ($user->status !== 'aktif') return $this->failForbidden('Akun nonaktif, hubungi admin');
        if (! password_verify($pwd, $user->pwd)) return $this->failUnauthorized('Password salah');

        $this->usersModel->update($user->id, ['last_login' => date('Y-m-d H:i:s')]);

        $guru = null;
        if ($user->role === 'guru') {
            $db = \Config\Database::connect();
            $guru = $db->table('mst_guru')
                ->select('mst_guru.*, mst_mapel.nm_mapel')
                ->join('mst_mapel', 'mst_mapel.id = mst_guru.mapel_id', 'left')
                ->where('mst_guru.user_id', $user->id)
                ->where('mst_guru.dlt_at', null)
                ->get()->getRow();
        }

        $token = JwtLibrary::encode(['user_id' => $user->id, 'username' => $user->username, 'role' => $user->role]);


        // Log login activity
        $this->logActivity($db, $user->id, $user->username, $user->role, 'LOGIN', 'Login berhasil');

        return $this->respond([
            'status' => 'success', 'message' => 'Login berhasil',
            'data' => ['token' => $token, 'user' => ['id' => $user->id, 'username' => $user->username, 'role' => $user->role], 'guru' => $guru],
        ]);
    }

    public function refresh()
    {
        $header = $this->request->getHeaderLine('Authorization');
        $token = substr($header, 7);
        try {
            $data = JwtLibrary::decode($token);
            $newToken = JwtLibrary::encode(['user_id' => $data->user_id, 'username' => $data->username, 'role' => $data->role]);
            return $this->respond(['status' => 'success', 'data' => ['token' => $newToken]]);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Token tidak valid');
        }
    }

    public function me()
    {
        $user = $this->usersModel->find($this->request->user_id);
        if (! $user) return $this->failNotFound('User tidak ditemukan');

        $guru = null;
        if ($user->role === 'guru') {
            $db = \Config\Database::connect();
            $guru = $db->table('mst_guru')
                ->select('mst_guru.*, mst_mapel.nm_mapel')
                ->join('mst_mapel', 'mst_mapel.id = mst_guru.mapel_id', 'left')
                ->where('mst_guru.user_id', $user->id)
                ->where('mst_guru.dlt_at', null)
                ->get()->getRow();
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'user' => ['id' => $user->id, 'username' => $user->username, 'role' => $user->role, 'status' => $user->status],
                'guru' => $guru,
            ],
        ]);
    }

    /**
     * Log user activity to activity_log table
     */
    private function logActivity($db, $userId, $username, $role, $action, $description = '')
    {
        try {
            $db->table('activity_log')->insert([
                'user_id'     => $userId,
                'username'    => $username,
                'role'        => $role,
                'action'      => $action,
                'description' => $description,
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => $this->request->getUserAgent()->__toString(),
                'crd_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'ActivityLog insert failed: ' . $e->getMessage());
        }
    }
}