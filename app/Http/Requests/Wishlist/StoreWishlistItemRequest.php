<?php

namespace App\Http\Requests\Wishlist;

use App\Http\Requests\BaseFormRequest;

class StoreWishlistItemRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'Số lượng phải ít nhất là 1.',
            'quantity.max' => 'Số lượng tối đa là 10.',
        ];
    }
}
