<?php

namespace App\Http\Requests;

use App\Enums\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * BaseFormRequest — lớp cha chung cho mọi FormRequest trong Homi.
 *
 * Trách nhiệm:
 *  - Chuẩn hoá response khi validation thất bại (JSON + error_code).
 *  - Chuẩn hoá response khi authorize() trả false (JSON 403).
 *  - Cung cấp helper tiện ích dùng lại trong subclass.
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Mặc định tất cả request đã qua middleware auth hoặc luôn cho phép.
     * Subclass override nếu cần logic phức tạp hơn.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trả JSON chuẩn khi validation thất bại thay vì redirect.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success'    => false,
                'message'    => 'Dữ liệu không hợp lệ.',
                'error_code' => ErrorCode::VALIDATION_ERROR->value,
                'errors'     => $validator->errors()->toArray(),
            ], 422)
        );
    }

    /**
     * Trả JSON chuẩn khi authorize() từ chối thay vì ném AuthorizationException mặc định.
     */
    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success'    => false,
                'message'    => 'Bạn không có quyền thực hiện thao tác này.',
                'error_code' => ErrorCode::UNAUTHORIZED->value,
            ], 403)
        );
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
