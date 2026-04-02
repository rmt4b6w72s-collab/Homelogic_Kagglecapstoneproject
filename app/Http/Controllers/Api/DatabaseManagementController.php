<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseManagementController extends Controller
{
    /**
     * Super admin access may be stored on users.role or only on Spatie roles — keep checks in sync with User model.
     */
    private function canManageDatabase(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'administrator'], true)) {
            return true;
        }

        return $user->hasRole('super_admin') || $user->hasRole('administrator');
    }

    /**
     * Get database statistics
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageDatabase($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Get database size
            $dbSize = $this->getDatabaseSize();
            
            // Get backup count
            $backupCount = $this->getBackupCount();
            
            // Get storage used
            $storageUsed = $this->getStorageUsed();

            return response()->json([
                'data' => [
                    'database_size' => $dbSize,
                    'total_backups' => $backupCount,
                    'storage_used' => $storageUsed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [
                    'database_size' => 'N/A',
                    'total_backups' => 0,
                    'storage_used' => 'N/A',
                    'auto_backup_enabled' => (bool) config('backup.scheduled_enabled', true),
                    'auto_backup_time' => config('backup.scheduled_time', '02:00'),
                    'auto_backup_keep' => (int) config('backup.scheduled_keep', 30),
                    'last_auto_backup_at' => null,
                    'last_auto_backup_filename' => null,
                ],
                'error' => 'Unable to fetch statistics',
            ], 200);
        }
    }

    /**
     * Create a database backup (manual; filename prefix backup_, not pruned automatically)
     */
    public function createBackup(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageDatabase($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = app(DatabaseBackupService::class)->createBackup(false);

        if (! ($result['success'] ?? false)) {
            $status = ($result['message'] ?? '') === 'Database file not found' ? 404 : 500;

            return response()->json([
                'message' => $result['message'] ?? 'Failed to create backup',
            ], $status);
        }

        return response()->json([
            'message' => 'Backup created successfully',
            'data' => [
                'filename' => $result['filename'],
                'size' => $result['size'],
                'created_at' => $result['created_at'],
            ],
        ]);
    }

    /**
     * Get list of recent backups
     */
    public function recentBackups(): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageDatabase($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $backupsDir = storage_path('app/backups');
            $backups = [];

            if (is_dir($backupsDir)) {
                $files = glob($backupsDir.'/backup_*.sql');

                foreach ($files as $file) {
                    $filename = basename($file);
                    $backups[] = [
                        'filename' => $filename,
                        'size' => $this->formatBytes(filesize($file)),
                        'created_at' => Carbon::createFromTimestamp(filemtime($file))->toIso8601String(),
                        'is_automatic' => str_starts_with($filename, 'backup_auto_'),
                    ];
                }

                // Sort by creation date, newest first
                usort($backups, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }

            return response()->json(['data' => $backups]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    }

    /**
     * Download a backup file
     */
    public function downloadBackup(Request $request, string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageDatabase($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $backupPath = storage_path("app/backups/{$filename}");

            // Security: backup_*.sql includes manual (backup_2026-...) and automatic (backup_auto_...)
            if (! str_starts_with($filename, 'backup_') || ! str_ends_with($filename, '.sql')) {
                return response()->json(['message' => 'Invalid backup file'], 400);
            }

            if (!file_exists($backupPath)) {
                return response()->json(['message' => 'Backup file not found'], 404);
            }

            return response()->download($backupPath, $filename, [
                'Content-Type' => 'application/sql',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore from a backup
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageDatabase($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $filename = $request->input('filename');
            $backupPath = storage_path("app/backups/{$filename}");

            if (!file_exists($backupPath)) {
                return response()->json(['message' => 'Backup file not found'], 404);
            }

            // Get database connection details
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            if (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
                set_time_limit(0);

                $restore = $this->runMysqlRestoreFromSqlFile($backupPath, $config);

                if (! $restore['ok']) {
                    Log::warning('Database restore failed', [
                        'detail' => $restore['detail'] ?? null,
                    ]);

                    return response()->json([
                        'message' => 'Failed to restore backup',
                        'detail' => $restore['detail'] ?? 'Unknown error (check server logs).',
                    ], 500);
                }
            } elseif ($config['driver'] === 'sqlite') {
                // Handle both absolute paths and relative paths
                $targetPath = $config['database'];
                if (!str_starts_with($targetPath, '/')) {
                    $targetPath = database_path($targetPath);
                }
                copy($backupPath, $targetPath);
            } else {
                return response()->json(['message' => 'Unsupported database driver'], 400);
            }

            return response()->json([
                'message' => 'Backup restored successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to restore backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh cache and optimize data
     */
    public function refreshData(): JsonResponse
    {
        $user = Auth::user();

        if (! $this->canManageDatabase($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Clear all caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Optimize database (if supported)
            try {
                Artisan::call('optimize:clear');
            } catch (\Exception $e) {
                // Ignore if optimize:clear doesn't exist
            }

            return response()->json([
                'message' => 'Data refreshed and cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to refresh data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore MySQL/MariaDB from a .sql file by piping stdin (avoids shell redirection and broken -p + escapeshellarg).
     *
     * @return array{ok: bool, detail?: string}
     */
    private function runMysqlRestoreFromSqlFile(string $backupPath, array $config): array
    {
        $parts = [config('backup.mysql_binary', 'mysql')];

        $socket = $config['unix_socket'] ?? '';
        if (is_string($socket) && $socket !== '') {
            $parts[] = '--socket='.$socket;
        } else {
            $parts[] = '-h';
            $parts[] = $config['host'] ?? '127.0.0.1';
            $port = (int) ($config['port'] ?? 3306);
            if ($port !== 3306) {
                $parts[] = '-P';
                $parts[] = (string) $port;
            }
        }

        $parts[] = '-u';
        $parts[] = $config['username'] ?? 'root';

        $password = $config['password'] ?? '';
        if ($password !== '') {
            // Single argv "-ppassword" — no shell; special characters in password are safe.
            $parts[] = '-p'.$password;
        }

        $parts[] = $config['database'] ?? '';

        $descriptorSpec = [
            0 => ['file', $backupPath, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($parts, $descriptorSpec, $pipes, null, null);

        if (! is_resource($process)) {
            return [
                'ok' => false,
                'detail' => 'Could not start the mysql client. Ensure the `mysql` CLI is installed and on PATH for the PHP/web user (e.g. www-data).',
            ];
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            return ['ok' => true];
        }

        $combined = trim($stderr !== '' ? $stderr : $stdout);
        if ($combined === '') {
            $combined = 'mysql exited with code '.$exitCode.'.';
        }

        if (strlen($combined) > 2000) {
            $combined = substr($combined, 0, 2000).'…';
        }

        return ['ok' => false, 'detail' => $combined];
    }

    /**
     * Get database size
     */
    private function getDatabaseSize(): string
    {
        try {
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            if ($config['driver'] === 'mysql') {
                $result = DB::select("SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                    FROM information_schema.tables 
                    WHERE table_schema = ?", [$config['database']]);
                
                if (!empty($result) && isset($result[0]->size_mb)) {
                    return $this->formatBytes($result[0]->size_mb * 1024 * 1024);
                }
            } elseif ($config['driver'] === 'sqlite') {
                // Handle both absolute paths and relative paths
                $dbPath = $config['database'];
                if (!str_starts_with($dbPath, '/')) {
                    $dbPath = database_path($dbPath);
                }
                if (file_exists($dbPath)) {
                    return $this->formatBytes(filesize($dbPath));
                }
            }

            return 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get backup count
     */
    private function getBackupCount(): int
    {
        try {
            $backupsDir = storage_path('app/backups');
            if (is_dir($backupsDir)) {
                return count(glob($backupsDir . '/backup_*.sql'));
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get storage used
     */
    private function getStorageUsed(): string
    {
        try {
            $storagePath = storage_path('app');
            $size = $this->getDirectorySize($storagePath);
            return $this->formatBytes($size);
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Calculate directory size recursively
     */
    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        return $size;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

}

