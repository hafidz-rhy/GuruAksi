<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PatchHistoryModel;
use CodeIgniter\API\ResponseTrait;
use Config\Database;
use ZipArchive;

class Patch extends BaseController
{
    use ResponseTrait;

    protected $model;
    protected $patchDir;
    protected $backupDir;
    protected $uploadDir;

    public function __construct()
    {
        $this->model     = new PatchHistoryModel();
        $this->patchDir  = WRITEPATH . 'patches/';
        $this->backupDir = WRITEPATH . 'patches/backups/';
        $this->uploadDir = WRITEPATH . 'patches/upload/';

        // Ensure directories exist
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);
        if (!is_dir($this->backupDir)) mkdir($this->backupDir, 0755, true);
    }

    /**
     * GET /api/patch/status
     * Return current app version, maintenance mode, and latest patch info
     */
    public function status()
    {
        $db = Database::connect();
        $maintenance = $db->table('pengaturan')->where('kunci', 'maintenance_mode')->get()->getRow();
        $isOn = $maintenance ? ($maintenance->nilai === '1' || $maintenance->nilai === 'true') : false;

        $version = $this->getCurrentVersion();

        $latest = $this->model->where('status', 'success')->orderBy('id', 'DESC')->first();

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'version'       => $version,
                'maintenance'   => $isOn,
                'env'           => ENVIRONMENT,
                'latest_patch'  => $latest ? [
                    'version'         => $latest->version,
                    'previous_version'=> $latest->previous_version,
                    'applied_at'      => $latest->applied_at,
                ] : null,
            ],
        ]);
    }

    /**
     * POST /api/patch/upload
     * Upload ZIP patch file, validate, return manifest preview
     */
    public function upload()
    {
        $file = $this->request->getFile('patch');
        if (!$file || !$file->isValid()) {
            return $this->fail('File patch tidak valid', 400);
        }

        if ($file->getClientExtension() !== 'zip') {
            return $this->fail('File harus berupa ZIP', 400);
        }

        // Move to upload dir
        $name = 'patch-' . time() . '.zip';
        $file->move($this->uploadDir, $name);
        $zipPath = $this->uploadDir . $name;

        // Validate & read manifest
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            unlink($zipPath);
            return $this->fail('File ZIP rusak atau tidak dapat dibuka', 400);
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        if (!$manifestRaw) {
            $zip->close();
            unlink($zipPath);
            return $this->fail('manifest.json tidak ditemukan dalam ZIP', 400);
        }

        $manifest = json_decode($manifestRaw, true);
        if (!$manifest || empty($manifest['version'])) {
            $zip->close();
            unlink($zipPath);
            return $this->fail('manifest.json tidak valid', 400);
        }

        // Check version constraint
        $currentVersion = $this->getCurrentVersion();
        $minVersion = $manifest['min_app_version'] ?? '0.0.0';
        if (version_compare($currentVersion, $minVersion, '<')) {
            $zip->close();
            unlink($zipPath);
            return $this->fail("Patch membutuhkan minimal versi $minVersion. Versi saat ini: $currentVersion", 400);
        }

        // Check previous version match
        if (!empty($manifest['previous_version']) && version_compare($currentVersion, $manifest['previous_version'], '!=')) {
            $zip->close();
            unlink($zipPath);
            return $this->fail("Patch ini untuk versi {$manifest['previous_version']}. Versi saat ini: $currentVersion", 409);
        }

        // Count files
        $fileCount = $zip->numFiles;
        $zip->close();

        // Create pending record
        $recordId = $this->model->insert([
            'version'         => $manifest['version'],
            'previous_version'=> $currentVersion,
            'file_name'       => $name,
            'file_size'       => filesize($zipPath),
            'manifest'        => json_encode($manifest),
            'status'          => 'pending',
            'applied_by'      => $this->getUserId(),
        ]);

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'record_id'  => $recordId,
                'file_name'  => $name,
                'file_count' => $fileCount - 1, // exclude manifest
                'manifest'   => $manifest,
            ],
        ]);
    }

    /**
     * POST /api/patch/apply
     * Apply a pending patch. Body: { record_id: int }
     */
    public function apply()
    {
        $data = $this->request->getJSON(true);
        $recordId = $data['record_id'] ?? null;
        if (!$recordId) return $this->fail('record_id diperlukan', 400);

        $record = $this->model->find($recordId);
        if (!$record || $record->status !== 'pending') {
            return $this->fail('Patch tidak ditemukan atau sudah diapply', 400);
        }

        $zipPath = $this->uploadDir . $record->file_name;
        if (!file_exists($zipPath)) {
            $this->model->update($recordId, ['status' => 'failed', 'error_message' => 'File ZIP hilang']);
            return $this->fail('File ZIP tidak ditemukan', 404);
        }

        $manifest = json_decode($record->manifest, true);
        $currentVersion = $this->getCurrentVersion();
        $backupFolder = $this->backupDir . 'v' . $currentVersion . '_' . date('YmdHis') . '/';

        try {
            // Step 1: Validate
            $this->model->update($recordId, ['status' => 'validating']);

            // Step 2: Backup
            $this->model->update($recordId, ['status' => 'backup']);
            $this->backupFiles($backupFolder);
            $this->backupDatabase($backupFolder);

            // Step 3: Enable maintenance mode
            $this->setMaintenance(true);

            // Step 4: Apply
            $this->model->update($recordId, ['status' => 'applying']);
            $this->extractPatch($zipPath, $manifest);

            // Step 5: Run migrations if any
            if (!empty($manifest['migrations'])) {
                $this->runMigrations($manifest['migrations']);
            }

            // Step 6: Run SQL if any
            if (!empty($manifest['sql'])) {
                $this->runSql($manifest['sql']);
            }

            // Step 7: Update version
            $this->setCurrentVersion($manifest['version']);

            // Step 8: Clear cache
            $this->clearCache();

            // Step 9: Disable maintenance mode
            $this->setMaintenance(false);

            // Step 10: Mark success
            $this->model->update($recordId, [
                'status'     => 'success',
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            // Clean up ZIP
            @unlink($zipPath);

            return $this->respond([
                'status'  => 'success',
                'message' => "Patch {$manifest['version']} berhasil diapplikasi",
                'data'    => ['version' => $manifest['version']],
            ]);
        } catch (\Throwable $e) {
            // Disable maintenance mode
            $this->setMaintenance(false);

            $this->model->update($recordId, [
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            log_message('error', 'Patch failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return $this->fail('Patch gagal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/patch/history
     * Return patch history
     */
    public function history()
    {
        $limit = $this->request->getGet('limit') ?? 20;
        $data = $this->model->orderBy('id', 'DESC')->findAll($limit);

        return $this->respond([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * POST /api/patch/rollback
     * Rollback to a previous version from backup
     */
    public function rollback()
    {
        $data = $this->request->getJSON(true);
        $version = $data['version'] ?? null;
        if (!$version) return $this->fail('version diperlukan', 400);

        $record = $this->model->where('version', $version)->where('status', 'success')->first();
        if (!$record) return $this->fail('Patch dengan versi tersebut tidak ditemukan', 404);

        $currentVersion = $this->getCurrentVersion();
        $backupDirs = glob($this->backupDir . 'v' . $currentVersion . '_*');
        if (empty($backupDirs)) return $this->fail('Backup untuk versi saat ini tidak ditemukan', 404);

        $backupFolder = $backupDirs[0];

        try {
            // Enable maintenance
            $this->setMaintenance(true);

            // Restore files
            $fileBackup = $backupFolder . '/files/';
            if (is_dir($fileBackup)) {
                $this->recurseCopy($fileBackup, ROOTPATH);
            }

            // Restore database
            $dbBackup = $backupFolder . '/database.sql';
            if (file_exists($dbBackup)) {
                $this->restoreDatabase($dbBackup);
            }

            // Restore version
            $this->setCurrentVersion($version);

            // Clear cache
            $this->clearCache();

            // Disable maintenance
            $this->setMaintenance(false);

            // Mark previous success patch as rolled back
            $currentPatch = $this->model
                ->where('version', $currentVersion)
                ->where('status', 'success')
                ->first();
            if ($currentPatch) {
                $this->model->update($currentPatch->id, ['status' => 'rolled_back']);
            }

            return $this->respond([
                'status'  => 'success',
                'message' => "Berhasil rollback ke versi $version",
            ]);
        } catch (\Throwable $e) {
            $this->setMaintenance(false);
            log_message('error', 'Rollback failed: ' . $e->getMessage());
            return $this->fail('Rollback gagal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/patch/maintenance
     * Toggle maintenance mode manually
     */
    public function maintenanceToggle()
    {
        $data = $this->request->getJSON(true);
        $on = !empty($data['on']);
        $this->setMaintenance($on);

        return $this->respond([
            'status'  => 'success',
            'message' => $on ? 'Maintenance mode ON' : 'Maintenance mode OFF',
            'data'    => ['maintenance' => $on],
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────

    private function getUserId()
    {
        return auth()->id() ?? null;
    }

    private function getCurrentVersion()
    {
        $file = ROOTPATH . 'VERSION';
        if (file_exists($file)) {
            return trim(file_get_contents($file));
        }
        return '0.0.0';
    }

    private function setCurrentVersion($version)
    {
        file_put_contents(ROOTPATH . 'VERSION', $version);
    }

    private function setMaintenance(bool $on)
    {
        $db = Database::connect();
        $existing = $db->table('pengaturan')->where('kunci', 'maintenance_mode')->get()->getRow();
        $val = $on ? '1' : '0';
        if ($existing) {
            $db->table('pengaturan')->where('kunci', 'maintenance_mode')->update(['nilai' => $val]);
        } else {
            $db->table('pengaturan')->insert(['kunci' => 'maintenance_mode', 'nilai' => $val]);
        }
    }

    private function backupFiles(string $backupFolder)
    {
        $fileBackup = $backupFolder . 'files/';
        if (!is_dir($fileBackup)) mkdir($fileBackup, 0755, true);

        // Copy all project files except writable and upload dirs
        $exclude = ['writable', 'vendor', 'node_modules'];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(ROOTPATH, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $relPath = substr($path, strlen(ROOTPATH));

            // Skip excluded
            $skip = false;
            foreach ($exclude as $ex) {
                if (strpos($relPath, $ex . DIRECTORY_SEPARATOR) === 0 || strpos($relPath, $ex) === 0) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $target = $fileBackup . $relPath;
            if ($item->isDir()) {
                if (!is_dir($target)) mkdir($target, 0755, true);
            } else {
                copy($path, $target);
            }
        }
    }

    private function backupDatabase(string $backupFolder)
    {
        $db = Database::connect();
        $tables = $db->listTables();
        $output = '';

        foreach ($tables as $table) {
            // Create table structure
            $createRow = $db->query("SHOW CREATE TABLE `$table`")->getRowArray();
            $output .= $createRow['Create Table'] . ";\n\n";

            // Get data
            $rows = $db->table($table)->get()->getResultArray();
            if (empty($rows)) continue;

            $output .= "INSERT INTO `$table` VALUES\n";
            $rowStrings = [];
            foreach ($rows as $row) {
                $vals = array_map(function ($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return "'" . $db->escapeString((string)$v) . "'";
                }, $row);
                $rowStrings[] = '(' . implode(',', $vals) . ')';
            }
            $output .= implode(",\n", $rowStrings) . ";\n\n";
        }

        file_put_contents($backupFolder . 'database.sql', $output);
    }

    private function extractPatch(string $zipPath, array $manifest)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Gagal membuka ZIP');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === 'manifest.json') continue;

            // Determine target path
            $targetPath = ROOTPATH . $entry;

            // Handle directory entry
            if (substr($entry, -1) === '/') {
                if (!is_dir($targetPath)) mkdir($targetPath, 0755, true);
                continue;
            }

            // Ensure parent directory exists
            $parentDir = dirname($targetPath);
            if (!is_dir($parentDir)) mkdir($parentDir, 0755, true);

            // Extract file
            $contents = $zip->getFromIndex($i);
            if ($contents !== false) {
                file_put_contents($targetPath, $contents);
            }
        }

        $zip->close();
    }

    private function runMigrations(array $migrationFiles)
    {
        $migrate = service('migrations');
        // Simply copy migration files to migrations dir and run
        // CI4 auto-detects new migrations
        try {
            $migrate->latest();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Migrasi gagal: ' . $e->getMessage());
        }
    }

    private function runSql(array $queries)
    {
        $db = Database::connect();
        foreach ($queries as $sql) {
            try {
                $db->query($sql);
            } catch (\Throwable $e) {
                throw new \RuntimeException('SQL gagal: ' . $e->getMessage() . ' | Query: ' . $sql);
            }
        }
    }

    private function clearCache()
    {
        // Clear CI4 cache
        $cacheDir = WRITEPATH . 'cache/';
        $this->deleteDirContents($cacheDir);

        // Regenerate routes
        if (file_exists(ROOTPATH . 'spark')) {
            try {
                @exec('php ' . ROOTPATH . 'spark cache:clear 2>&1');
            } catch (\Throwable $e) {
                // spark might not be available
            }
        }
    }

    private function deleteDirContents(string $dir)
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
    }

    private function recurseCopy(string $src, string $dst)
    {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            if (is_dir($src . '/' . $file)) {
                $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($dir);
    }

    private function restoreDatabase(string $file)
    {
        $sql = file_get_contents($file);
        $db = Database::connect();

        $queries = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($queries as $query) {
            if (empty($query)) continue;
            try {
                $db->query($query);
            } catch (\Throwable $e) {
                // Skip errors on restore (table may already exist, etc.)
                log_message('warning', 'Restore SQL warning: ' . $e->getMessage());
            }
        }
    }
}