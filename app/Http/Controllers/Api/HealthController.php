<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health-check endpoint dùng để kiểm tra server và kết nối database.
     *
     * GET /api/health
     */
    public function __invoke(): JsonResponse
    {
        $databaseStatus = 'error';

        try {
            DB::connection()->getPdo();
            $databaseStatus = 'ok';
        } catch (\Throwable $e) {
            $databaseStatus = 'error: '.$e->getMessage();
        }

        return response()->json([
            'success' => true,
            'message' => 'Server is running',
            'data' => [
                'app' => config('app.name'),
                'env' => config('app.env'),
                'time' => now()->toIso8601String(),
                'database' => $databaseStatus,
            ],
        ]);
    }
}
