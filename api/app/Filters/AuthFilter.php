<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Libraries\JwtLibrary;

class AuthFilter implements FilterInterface
{
    /**
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');


        // Fallback: pada Apache dengan mod_rewrite, header Authorization
        // bisa berada di REDIRECT_HTTP_AUTHORIZATION setelah internal redirect
        if (empty($header) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (empty($header) || !str_starts_with($header, 'Bearer ')) {
            return service('response')->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Token tidak ditemukan',
            ]);
        }

        $token = substr($header, 7);

        try {
            $data = JwtLibrary::decode($token);

            // Set data user ke request untuk digunakan controller
            $request->user_id    = $data->user_id;
            $request->username   = $data->username;
            $request->role       = $data->role;
        } catch (\Exception $e) {
            return service('response')->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Token tidak valid atau kadaluarsa',
            ]);
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing
    }
}