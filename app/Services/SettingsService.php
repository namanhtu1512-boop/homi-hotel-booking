<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Lưu/đọc cấu hình hệ thống dạng key-value (bảng settings).
 * Không cache — quy mô cấu hình nhỏ, ưu tiên đơn giản hơn tối ưu tốc độ.
 */
class SettingsService
{
    public function all(): array
    {
        return Setting::pluck('value', 'key')->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Lưu nhiều cặp key => value cùng lúc (dùng cho mỗi tab form Cài đặt).
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
