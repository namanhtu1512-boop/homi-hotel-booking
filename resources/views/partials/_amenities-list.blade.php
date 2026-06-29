{{-- Partial: _amenities-list
    Props:
        $amenities — Collection<Amenity>
        $title     — string tiêu đề phần (default: 'Tiện nghi')
--}}
@if ($amenities->isNotEmpty())
    <div class="section-kicker" style="margin-top: 22px;">{{ $title ?? 'Tiện nghi' }}</div>
    <div class="room-card-meta" style="margin-top: 10px; gap: 10px; flex-wrap: wrap;">
        @foreach ($amenities as $amenity)
            <span class="badge badge-blue">{{ $amenity->name }}</span>
        @endforeach
    </div>
@endif
