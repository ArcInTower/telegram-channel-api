<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TelegramSessionInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:session-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display information about current Telegram session files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Telegram Session Information');
        $this->info('===========================');

        $sessionBase = storage_path('app/telegram.madeline');
        $sessionFiles = [
            $sessionBase,
            $sessionBase . '.lock',
            $sessionBase . '.temp.madeline',
            $sessionBase . '.lightState.php',
            $sessionBase . '.lightState.php.lock',
            $sessionBase . '.safe.php',
            $sessionBase . '.safe.php.lock',
            $sessionBase . '.ipcState.php',
            $sessionBase . '.ipcState.php.lock',
        ];

        $this->newLine();
        $this->info('Session files in: ' . storage_path('app/'));
        $this->newLine();

        $foundFiles = [];
        $totalSize = 0;

        // Check main session (might be directory)
        if (is_dir($sessionBase)) {
            $size = $this->getDirectorySize($sessionBase);
            $totalSize += $size;
            $foundFiles[] = [
                'File' => basename($sessionBase) . '/',
                'Type' => 'Directory',
                'Size' => $this->formatBytes($size),
                'Modified' => date('Y-m-d H:i:s', filemtime($sessionBase)),
            ];

            // List directory contents
            $files = File::allFiles($sessionBase);
            foreach ($files as $file) {
                $foundFiles[] = [
                    'File' => '  └─ ' . $file->getRelativePathname(),
                    'Type' => 'File',
                    'Size' => $this->formatBytes($file->getSize()),
                    'Modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Check individual files
        foreach ($sessionFiles as $file) {
            if (file_exists($file) && !is_dir($file)) {
                $size = filesize($file);
                $totalSize += $size;
                $foundFiles[] = [
                    'File' => basename($file),
                    'Type' => 'File',
                    'Size' => $this->formatBytes($size),
                    'Modified' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }
        }

        if (empty($foundFiles)) {
            $this->warn('No session files found.');
            $this->info('Run "php artisan telegram:login" to create a new session.');

            return Command::SUCCESS;
        }

        $this->table(['File', 'Type', 'Size', 'Modified'], $foundFiles);

        $this->newLine();
        $this->info('Total size: ' . $this->formatBytes($totalSize));
        $this->info('Total files: ' . count($foundFiles));

        // Cache information
        $this->newLine();
        $this->info('Cache Configuration:');
        $this->info('- Cache driver: ' . config('cache.default'));
        $this->info('- Message cache TTL: ' . config('telegram.cache_ttl', 300) . ' seconds');
        $this->info('- Statistics cache TTL: ' . config('telegram.statistics_cache_ttl', 3600) . ' seconds');

        return Command::SUCCESS;
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
