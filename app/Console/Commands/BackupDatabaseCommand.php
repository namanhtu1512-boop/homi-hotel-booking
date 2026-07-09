<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Tuần 16 (BE2): sao lưu database ra file — dùng làm phương án dự phòng khi
 * demo/deploy lỗi (theo checklist nghiệm thu Sprint 8: "có file SQL/seed dự
 * phòng"). Hỗ trợ cả 2 driver dùng trong đồ án: mysql (mysqldump) và sqlite
 * (copy file .sqlite).
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'homi:backup-database {--path=storage/app/backups}';

    protected $description = 'Sao lưu database hiện tại ra file (mysqldump cho MySQL, copy file cho SQLite)';

    public function handle(): int
    {
        $connection = config('database.default');
        $config     = config("database.connections.{$connection}");

        $directory = base_path($this->option('path'));
        File::ensureDirectoryExists($directory);

        $timestamp = now()->format('Y-m-d_His');

        return match ($connection) {
            'sqlite' => $this->backupSqlite($config, $directory, $timestamp),
            'mysql'  => $this->backupMysql($config, $directory, $timestamp),
            default  => $this->unsupportedDriver($connection),
        };
    }

    private function backupSqlite(array $config, string $directory, string $timestamp): int
    {
        $source = $config['database'];

        if (! File::exists($source)) {
            $this->error("Không tìm thấy file SQLite tại: {$source}");

            return self::FAILURE;
        }

        $destination = "{$directory}/homi_backup_{$timestamp}.sqlite";
        File::copy($source, $destination);

        $this->info("Đã sao lưu SQLite vào: {$destination}");

        return self::SUCCESS;
    }

    private function backupMysql(array $config, string $directory, string $timestamp): int
    {
        $destination = "{$directory}/homi_backup_{$timestamp}.sql";

        $command = [
            'mysqldump',
            '-h', $config['host'] ?? '127.0.0.1',
            '-P', (string) ($config['port'] ?? 3306),
            '-u', $config['username'] ?? 'root',
            $config['database'],
        ];

        $process = new Process($command, null, [
            'MYSQL_PWD' => $config['password'] ?? '',
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('mysqldump thất bại: ' . $process->getErrorOutput());

            return self::FAILURE;
        }

        File::put($destination, $process->getOutput());
        $this->info("Đã sao lưu MySQL vào: {$destination}");

        return self::SUCCESS;
    }

    private function unsupportedDriver(string $connection): int
    {
        $this->error("Driver '{$connection}' chưa được hỗ trợ. Chỉ hỗ trợ mysql hoặc sqlite.");

        return self::FAILURE;
    }
}
