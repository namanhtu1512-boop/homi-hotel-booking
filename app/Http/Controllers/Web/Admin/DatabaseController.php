<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseController extends Controller
{
    /**
     * Cột nhạy cảm không bao giờ được nạp/hiển thị ở trang xem nhanh DB này —
     * loại ngay từ câu SELECT (không phải chỉ che ở view) để không có cách
     * nào rò rỉ ra ngoài qua title attribute, export, hay debug.
     */
    private const SENSITIVE_COLUMNS = ['password', 'remember_token'];

    public function index()
    {
        $tables = [
            'users',
            'hotel_info',
            'room_types',
            'bookings',
            'booking_items',
            'payments',
        ];

        $database = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_diff(Schema::getColumnListing($table), self::SENSITIVE_COLUMNS));

            $database[$table] = [
                'columns' => $columns,
                'count' => DB::table($table)->count(),
                'rows' => DB::table($table)->select($columns)->limit(20)->get(),
            ];
        }

        return view('admin.database.index', compact('database'));
    }
}