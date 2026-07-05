<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

/**
 * Đổi email có luồng riêng (xem ChangeEmailRequest/ProfileController::updateEmail())
 * — request này KHÔNG validate 'email' để tránh trường hợp form profile lỡ thêm
 * input email rồi tưởng nó được lưu (ProfileController::update() chỉ persist
 * name/phone/address/avatar).
 */
class UpdateProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Họ tên không được để trống.',
        ];
    }
}
