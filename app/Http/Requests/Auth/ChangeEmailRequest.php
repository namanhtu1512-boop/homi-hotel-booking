<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class ChangeEmailRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email'             => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password'  => ['required', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'                     => 'Vui lòng nhập email mới.',
            'email.email'                         => 'Email không đúng định dạng.',
            'email.unique'                        => 'Email đã được sử dụng.',
            'current_password.required'           => 'Vui lòng nhập mật khẩu hiện tại để xác nhận.',
            'current_password.current_password'   => 'Mật khẩu hiện tại không đúng.',
        ];
    }
}
