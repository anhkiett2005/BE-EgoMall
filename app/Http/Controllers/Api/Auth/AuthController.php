<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Cookie;
use App\Models\User;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;



class AuthController extends Controller
{
    /**
     * Register a new customer and set JWT cookie.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['role_id']  = 4;

        $user = User::create($data);

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Cannot create token'], 500);
        }

        $cookie = new Cookie(
            'token',
            $token,
            now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            '/',
            null,
            config('app.env') === 'production',
            true,
            false,
            Cookie::SAMESITE_LAX
        );

        return (new UserResource($user))
            ->response()
            ->withCookie($cookie);
    }

    /**
     * Login user, create JWT and set cookie.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Cannot create token'], 500);
        }

        try {
            $user = JWTAuth::user();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Cannot retrieve user'], 500);
        }

        $cookie = new Cookie(
            'token',
            $token,
            now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            '/',
            null,
            config('app.env') === 'production',
            true,
            false,
            Cookie::SAMESITE_LAX
        );

        return (new UserResource($user))
            ->response()
            ->withCookie($cookie);
    }

    /**
     * Get current authenticated user.
     */
    public function user(): JsonResponse
    {
        try {
            $user = JWTAuth::user();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return (new UserResource($user))
            ->response();
    }

    /**
     * Logout user by invalidating token and clearing cookie.
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Cannot logout'], 500);
        }

        $cookie = new Cookie(
            'token',
            '',
            now()->subMinute()->getTimestamp(),
            '/',
            null,
            config('app.env') === 'production',
            true,
            false,
            Cookie::SAMESITE_LAX
        );

        return response()
            ->json(['message' => 'Logged out'])
            ->withCookie($cookie);
    }

    /**
     * Refresh JWT token and update cookie.
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Cannot refresh token'], 500);
        }

        try {
            $user = JWTAuth::authenticate($newToken);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Cannot authenticate user'], 500);
        }

        $cookie = new Cookie(
            'token',
            $newToken,
            now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            '/',
            null,
            config('app.env') === 'production',
            true,
            false,
            Cookie::SAMESITE_LAX
        );

        return (new UserResource($user))
            ->response()
            ->withCookie($cookie);
    }

    /**
     * Cập nhật profile người dùng hiện tại.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        /** @var \App\Models\User $user */

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('avatars', 'public');
            $data['image'] = $path;
        }
        $user->update($data);
        return (new UserResource($user))
            ->response();
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        // 1. Lấy user hiện tại
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        // 2. Kiểm tra mật khẩu cũ
        if (! Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'error' => 'Mật khẩu cũ không đúng.'
            ], 422);
        }

        // 3. Lưu mật khẩu mới
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 4. Trả về thông báo thành công
        return response()->json([
            'message' => 'Đổi mật khẩu thành công.'
        ]);
    }
}