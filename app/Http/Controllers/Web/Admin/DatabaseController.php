<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseController extends Controller
{
    public function index()
    {
        $tables = [
            'users',
            'hotels',
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

            $columns = Schema::getColumnListing($table);

            $database[$table] = [
                'columns' => $columns,
                'count' => DB::table($table)->count(),
                'rows' => DB::table($table)->limit(20)->get(),
            ];
        }

        return view('admin.database.index', compact('database'));
    }
}