<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BaseFormRequest — lớp cha chung cho mọi FormRequest trong Homi.
 *
 * Dùng hành vi mặc định của Laravel: validate lỗi trên form Blade sẽ
 * redirect back kèm session errors (hiển thị qua $errors->any() trong view).
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Mặc định cho phép — subclass override nếu cần logic phức tạp hơn.
     */
    public function authorize(): bool
    {
        return true;
    }

    // ----------------------------------------------------------------
    // Helper utilities dùng chung cho subclass
    // ----------------------------------------------------------------

    /**
     * Trả về giá trị boolean an toàn từ request (hỗ trợ "1"/"0", "true"/"false").
     */
    protected function boolInput(string $key, bool $default = false): bool
    {
        $value = $this->input($key);

        if (is_null($value)) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Trim string input, trả về null nếu rỗng.
     */
    protected function trimmedString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
