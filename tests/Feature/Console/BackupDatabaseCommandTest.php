<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 16 (BE2): lệnh sao lưu database dùng làm phương án dự phòng khi
 * demo/deploy lỗi. Môi trường test luôn chạy SQLite ":memory:" (không phải
 * file thật) nên chỉ xác nhận lệnh chạy được và báo lỗi rõ ràng thay vì
 * crash — việc sao lưu thật (mysqldump/copy file) đã được chạy tay để xác
 * nhận hoạt động đúng trên máy có MySQL cài sẵn.
 */
class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_without_crashing_on_in_memory_sqlite(): void
    {
        $this->artisan('homi:backup-database', ['--path' => 'storage/app/backups-test'])
            ->assertExitCode(1);
    }
}
