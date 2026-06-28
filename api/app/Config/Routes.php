<?php

use CodeIgniter\Router\RouteCollection;

$routes->post('api/auth/login', 'Api\Auth::login');
$routes->post('api/auth/refresh', 'Api\Auth::refresh');
$routes->get('api/auth/me', 'Api\Auth::me', ['filter' => 'auth']);

// Presensi QR — PUBLIC endpoints (no auth for QR display & verify)
$routes->get('api/presensi/status', 'Api\Presensi::qrStatus');
$routes->get('api/presensi/verify/(:segment)', 'Api\Presensi::verifyQr/$1');

// Public: get logo madrasah (no auth needed)
$routes->get('api/public/logo', 'Api\Pengaturan::logoPublic');

// Public: get recaptcha config (no auth needed)
$routes->get('api/public/recaptcha', 'Api\Pengaturan::recaptchaPublic');

$routes->group('api', ['filter' => 'auth'], static function ($routes) {
    $routes->get('tahun-pelajaran', 'Api\TahunPelajaran::index');
    $routes->get('tahun-pelajaran/aktif', 'Api\TahunPelajaran::aktif');
    $routes->get('tahun-pelajaran/(:num)', 'Api\TahunPelajaran::show/$1');
    $routes->post('tahun-pelajaran', 'Api\TahunPelajaran::create');
    $routes->put('tahun-pelajaran/(:num)', 'Api\TahunPelajaran::update/$1');
    $routes->delete('tahun-pelajaran/(:num)', 'Api\TahunPelajaran::delete/$1');
    $routes->post('tahun-pelajaran/(:num)/aktifkan', 'Api\TahunPelajaran::aktifkan/$1');

    $routes->get('guru', 'Api\Guru::index');
    $routes->get('guru/(:num)', 'Api\Guru::show/$1');
    $routes->post('guru', 'Api\Guru::create');
    $routes->put('guru/(:num)', 'Api\Guru::update/$1');
    $routes->delete('guru/(:num)', 'Api\Guru::delete/$1');
    $routes->patch('guru/(:num)/status', 'Api\Guru::status/$1');

    $routes->get('mapel', 'Api\Mapel::index');
    $routes->get('mapel/(:num)', 'Api\Mapel::show/$1');
    $routes->post('mapel', 'Api\Mapel::create');
    $routes->put('mapel/(:num)', 'Api\Mapel::update/$1');
    $routes->delete('mapel/(:num)', 'Api\Mapel::delete/$1');

    $routes->get('kelas', 'Api\Kelas::index');
    $routes->get('kelas/jenjang', 'Api\Kelas::jenjang');
    $routes->get('kelas/(:num)', 'Api\Kelas::show/$1');
    $routes->post('kelas', 'Api\Kelas::create');
    $routes->put('kelas/(:num)', 'Api\Kelas::update/$1');
    $routes->delete('kelas/(:num)', 'Api\Kelas::delete/$1');

    $routes->get('jam', 'Api\Jam::index');
    $routes->get('jam/(:num)', 'Api\Jam::show/$1');
    $routes->post('jam', 'Api\Jam::create');
    $routes->put('jam/(:num)', 'Api\Jam::update/$1');
    $routes->delete('jam/(:num)', 'Api\Jam::delete/$1');
    $routes->post('jam/generate', 'Api\Jam::generate');

    $routes->get('siswa', 'Api\Siswa::index');
    $routes->get('siswa/(:num)', 'Api\Siswa::show/$1');
    $routes->post('siswa', 'Api\Siswa::create');
    $routes->put('siswa/(:num)', 'Api\Siswa::update/$1');
    $routes->delete('siswa/(:num)', 'Api\Siswa::delete/$1');
    $routes->post('siswa/naik-kelas', 'Api\Siswa::naikKelas');

    $routes->get('users', 'Api\Users::index');
    $routes->post('users', 'Api\Users::create');
    $routes->put('users/(:num)', 'Api\Users::update/$1');
    $routes->delete('users/(:num)', 'Api\Users::delete/$1');
    $routes->patch('users/(:num)/reset-password', 'Api\Users::resetPassword/$1');

    $routes->get('jadwal', 'Api\Jadwal::index');
    $routes->get('jadwal/kelas/(:num)', 'Api\Jadwal::byKelas/$1');
    $routes->post('jadwal/assign', 'Api\Jadwal::assign');
    $routes->get('jadwal/(:num)', 'Api\Jadwal::show/$1');
    $routes->post('jadwal', 'Api\Jadwal::create');
    $routes->put('jadwal/(:num)', 'Api\Jadwal::update/$1');
    $routes->delete('jadwal/(:num)', 'Api\Jadwal::delete/$1');
    $routes->get('jadwal/hari-ini', 'Api\Jadwal::hariIni');
    $routes->get('jadwal/guru/(:num)', 'Api\Jadwal::byGuru/$1');

    $routes->get('kehadiran', 'Api\Presensi::index');
    $routes->get('kehadiran/guru/(:num)', 'Api\Presensi::riwayat/$1');
    $routes->post('kehadiran', 'Api\Presensi::create');
    $routes->post('kehadiran/scan', 'Api\Presensi::scan');

    $routes->post('presensi/generate', 'Api\Presensi::generateQr');

    $routes->get('jurnal', 'Api\Jurnal::index');
    $routes->get('jurnal/(:num)', 'Api\Jurnal::show/$1');
    $routes->post('jurnal', 'Api\Jurnal::create');
    $routes->put('jurnal/(:num)', 'Api\Jurnal::update/$1');
    $routes->delete('jurnal/(:num)', 'Api\Jurnal::delete/$1');
    $routes->get('jurnal/hari-ini', 'Api\Jurnal::hariIni');

    $routes->get('agenda', 'Api\Agenda::index');
    $routes->get('agenda/(:num)', 'Api\Agenda::show/$1');
    $routes->post('agenda', 'Api\Agenda::create');
    $routes->put('agenda/(:num)', 'Api\Agenda::update/$1');
    $routes->delete('agenda/(:num)', 'Api\Agenda::delete/$1');
    $routes->patch('agenda/(:num)/status', 'Api\Agenda::toggleStatus/$1');

    $routes->get('activity', 'Api\ActivityLog::index');

    $routes->get('pengaturan', 'Api\Pengaturan::index');
    $routes->get('pengaturan/(:segment)', 'Api\Pengaturan::show/$1');
    $routes->post('pengaturan', 'Api\Pengaturan::update');
    $routes->post('pengaturan/upload-logo', 'Api\Pengaturan::uploadLogo');

    $routes->get('patch/status', 'Api\Patch::status');
    $routes->get('patch/history', 'Api\Patch::history');
    $routes->post('patch/upload', 'Api\Patch::upload');
    $routes->post('patch/apply', 'Api\Patch::apply');
    $routes->post('patch/rollback', 'Api\Patch::rollback');
    $routes->post('patch/maintenance', 'Api\Patch::maintenanceToggle');

    $routes->get('penugasan', 'Api\Penugasan::index');
    $routes->get('penugasan/mapel-oleh-guru/(:num)', 'Api\Penugasan::mapelOlehGuru/$1');
    $routes->get('penugasan/(:num)', 'Api\Penugasan::show/$1');
    $routes->post('penugasan', 'Api\Penugasan::create');
    $routes->post('penugasan/batch', 'Api\Penugasan::batch');
    $routes->put('penugasan/(:num)', 'Api\Penugasan::update/$1');
    $routes->delete('penugasan/(:num)', 'Api\Penugasan::delete/$1');

    $routes->post('import/siswa', 'Api\Import::siswa');
    $routes->post('import/guru', 'Api\Import::guru');

    $routes->get('dashboard/admin', 'Api\Dashboard::admin');
    $routes->get('dashboard/guru', 'Api\Dashboard::guru');
    $routes->get('dashboard/kamad', 'Api\Dashboard::kamad');
});