<?php

namespace App\Http\Requests\RoomType\Concerns;

/**
 * Validate textarea "mỗi dòng 1 đường dẫn ảnh" (images_text) — giới hạn
 * mỗi dòng tối đa 500 ký tự, khớp với cách RoomTypeController::withImages()
 * thực sự parse field này (trước đây có rule 'images.*' validate nhầm một
 * field 'images' không hề được gửi lên, nên giới hạn độ dài chưa từng được
 * áp dụng trên dữ liệu thật).
 */
trait ValidatesImageText
{
    protected function eachImageLineMax500(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            foreach (explode("\n", (string) $value) as $line) {
                if (mb_strlen(trim($line)) > 500) {
                    $fail('Mỗi đường dẫn ảnh tối đa 500 ký tự.');

                    return;
                }
            }
        };
    }
}
