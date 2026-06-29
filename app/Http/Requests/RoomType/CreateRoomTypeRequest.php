<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\RoomType\Concerns\HasRoomTypeAttributes;
use App\Http\Requests\RoomType\Concerns\ValidatesImageText;

class CreateRoomTypeRequest extends BaseFormRequest
{
    use HasRoomTypeAttributes, ValidatesImageText;

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity'        => ['required', 'integer', 'min:1'],
            'bed_type'        => ['nullable', 'string', 'max:100'],
            'area'            => ['nullable', 'numeric', 'min:0'],
            'total_rooms'     => ['required', 'integer', 'min:1'],
            'images_text'     => ['nullable', 'string', $this->eachImageLineMax500()],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $data['images'] = collect(explode("\n", $data['images_text'] ?? ''))
            ->map(fn(string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();

        unset($data['images_text']);

        return $data;
    }
}
