<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $db = \Config\Database::connect();
        $row = $db->table('pengaturan')->where('kunci', 'maintenance_mode')->get()->getRow();

        if ($row && ($row->nilai === '1' || $row->nilai === 'true')) {
            // Allow auth endpoints so admin can still login
            $path = $request->getPath();
            if (strpos($path, 'auth/') !== false) {
                return;
            }

            // Return maintenance response
            $response = service('response');
            $response->setStatusCode(503);
            $response->setJSON([
                'status'  => 'maintenance',
                'message' => 'Aplikasi sedang dalam perbaikan. Silakan coba beberapa saat lagi.',
            ]);
            return $response;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action
    }
}