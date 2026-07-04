<?php

namespace App\Http\Requests\Wishlist;

use App\Http\Requests\BaseFormRequest;

class UpdateWishlistItemRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'adults'   => ['required', 'integer', 'min:1', 'max:50'],
            'children' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.min'      => 'Số lượng phải ít nhất là 1.',
            'quantity.max'      => 'Số lượng tối đa là 10.',
            'adults.required'   => 'Vui lòng nhập số người lớn.',
            'adults.min'        => 'Phải có ít nhất 1 người lớn.',
        ];
    }
}
