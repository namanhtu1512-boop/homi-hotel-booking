<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Mật khẩu hiện tại không được để trống.',
            'password.required'         => 'Mật khẩu mới không được để trống.',
            'password.min'              => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'password.confirmed'        => 'Xác nhận mật khẩu mới không khớp.',
        ];
    }
}
