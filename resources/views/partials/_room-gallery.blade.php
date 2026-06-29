{{-- Partial: _room-gallery
    Props:
        $images  — Collection<RoomTypeImage> (với image_url accessor)
        $alt     — string tên phòng dùng cho alt text
--}}
@if ($images->isNotEmpty())
    <div class="hotel-gallery-main" style="background-image: url('{{ $images->first()->image_url }}');"
         role="img" aria-label="Ảnh phòng: {{ $alt }}"></div>

    @if ($images->count() > 1)
        <div class="hotel-gallery-thumbs">
            @foreach ($images->skip(1) as $image)
                <div class="hotel-gallery-thumb" style="background-image: url('{{ $image->image_url }}');"
                     role="img" aria-label="{{ $alt }}"></div>
            @endforeach
        </div>
    @endif
@else
    <div class="hotel-gallery-main" role="img" aria-label="Chưa có ảnh">
        <span style="color: var(--primary); font-weight: 700;">Chưa có ảnh</span>
    </div>
@endif
