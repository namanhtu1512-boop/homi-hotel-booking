<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'customer',
            'status'   => 'active',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return $this->created(['token' => $token, 'user' => $user], 'Đăng ký thành công.');
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($data)) {
            return $this->error('Email hoặc mật khẩu không đúng.', 401);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return $this->error('Tài khoản đang bị khóa.', 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return $this->success(['token' => $token, 'user' => $user], 'Đăng nhập thành công.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($data);

        return $this->success($request->user()->fresh(), 'Cập nhật thành công.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $request->user()->password)) {
            return $this->error('Mật khẩu hiện tại không đúng.', 422);
        }

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return $this->success([], 'Đổi mật khẩu thành công.');
    }

    public function logout(Request $request): JsonResponse
    {
        // currentAccessToken() trả về TransientToken (không có delete()) khi
        // request được xác thực qua session guard thay vì Bearer token thật
        // — Sanctum hỗ trợ cả 2 kiểu song song nên phải kiểm tra trước khi
        // gọi delete(), tránh 500 cho client xác thực qua session.
        $token = $request->user()->currentAccessToken();

        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return $this->success([], 'Đã đăng xuất.');
    }
}
