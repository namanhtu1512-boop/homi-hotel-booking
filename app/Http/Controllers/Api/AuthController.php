<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'address'  => $request->address,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
            'status'   => 'active',
        ]);

        $token = $user->createToken('homi_token')->plainTextToken;

        return $this->created([
            'user'  => $user,
            'token' => $token,
        ], 'Đăng ký tài khoản thành công.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email hoặc mật khẩu không đúng.', 401);
        }

        if ($user->status === 'locked') {
            return $this->error('Tài khoản đã bị khóa.', 403);
        }

        $token = $user->createToken('homi_token')->plainTextToken;

        return $this->success([
            'user'  => $user,
            'token' => $token,
        ], 'Đăng nhập thành công.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(['user' => $request->user()]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return $this->success(['user' => $request->user()->fresh()], 'Cập nhật hồ sơ thành công.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Mật khẩu hiện tại không đúng.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Thu hồi tất cả token, buộc đăng nhập lại
        $user->tokens()->delete();

        return $this->success(message: 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success(message: 'Đăng xuất thành công.');
    }
}
