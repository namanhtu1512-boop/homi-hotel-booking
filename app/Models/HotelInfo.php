<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * HotelInfo — bảng singleton: hệ thống Homi chỉ vận hành 1 khách sạn duy
 * nhất nên KHÔNG có CRUD danh sách/tạo mới/xóa, chỉ có xem và cập nhật
 * bản ghi duy nhất. Dùng HotelInfo::instance() để luôn lấy đúng bản ghi đó
 * (tự tạo bản ghi mặc định nếu DB chưa có, tránh lỗi "no hotel configured").
 *
 * room_types KHÔNG có hotel_id vì chỉ có 1 khách sạn — mọi loại phòng mặc
 * định đều thuộc về khách sạn duy nhất này.
 */
class HotelInfo extends Model
{
    use HasFactory;

    protected $table = 'hotel_info';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'description',
        'check_in_time',
        'check_out_time',
        'policies',
        'star_rating',
        'status',
        'weekend_surcharge_percent',
        'child_surcharge_per_night',
    ];

    protected $casts = [
        'star_rating'               => 'integer',
        'status'                    => 'string',
        'weekend_surcharge_percent' => 'decimal:2',
        'child_surcharge_per_night' => 'integer',
    ];

    // --- Relationships ---

    public function images()
    {
        return $this->hasMany(HotelInfoImage::class)->orderBy('sort_order');
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'hotel_info_amenity');
    }

    // --- Singleton accessor ---

    /**
     * Luôn trả về bản ghi khách sạn duy nhất, tự tạo bản ghi mặc định
     * nếu bảng hotel_info đang rỗng (vd: môi trường mới chưa seed).
     */
    public static function instance(): self
    {
        return static::query()->first() ?? static::create([
            'name'    => 'Homi Hotel',
            'address' => 'Đang cập nhật',
            'status'  => 'active',
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
