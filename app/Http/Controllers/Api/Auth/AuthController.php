<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Resources\UserResource;
use App\Notifications\OtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Cookie;
use App\Models\User;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordWithOtpRequest;
use App\Response\ApiResponse;
use Exception;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Đăng ký khách hàng mới, lưu OTP và gửi mail.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['role_id'] = 4;

        // Tạo user, is_active mặc định false
        $user = User::create(array_merge($data, ['is_active' => false]));

        // Sinh OTP 6 chữ số
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->otp_sent_count = 1;
        $user->otp_sent_at = now();
        $user->save();

        // Gửi mail OTP
        $user->notify(new OtpNotification($otp, 5));

        return response()->json([
            'message' => 'OTP đã được gửi về email của bạn. Vui lòng kiểm tra.'
        ]);
    }

    /**
     * Xác thực OTP đăng ký, kích hoạt tài khoản và cấp JWT.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || $user->otp !== $request->otp || now()->gt($user->otp_expires_at)) {
            return response()->json(['error' => 'OTP không hợp lệ hoặc đã hết hạn'], 422);
        }

        // Kích hoạt user
        $user->is_active = true;
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        // Cấp JWT
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Không thể tạo token'], 500);
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
     * Đăng nhập bình thường: phát JWT và cookie.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Email hoặc mật khẩu không đúng'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Không thể tạo token'], 500);
        }

        $user = JWTAuth::user();
        $cookie = new Cookie('token', $token, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);

        return (new UserResource($user))
            ->response()
            ->withCookie($cookie);
    }


    /**
     * Đăng nhập với google
     */
    public function redirectToGoogle()
    {
        $redirectUrl = Socialite::driver('google')
                                ->stateless()
                                ->redirect()
                                ->getTargetUrl();

        return ApiResponse::success('Google redirect URL generated successfully',data: [
            'url' => $redirectUrl
        ]);
    }

    /**
     * Xử lý call back google trả về và tạo phiên đăng nhập
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $googleUser->getId())
                        ->orWhere('email', $googleUser->getEmail())->first();

            // check nếu tài khoản này đã login bằng facebook rồi thì trả về lỗi duplicate email
            if($user && $user->facebook_id !== null) {
                throw new ApiException("Email {$user->email} đã được sử dụng bởi một phương thức đăng nhập khác " . ucfirst('google'), 409);
            }

            if(!$user) {
                // nếu ch có tạo mới tài khoản
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(16)),
                    'google_id' => $googleUser->getId(),
                    'image' => $googleUser->getAvatar(),
                    'role_id' => 4,
                    'is_active' => true
                ]);
            }
            // Tạo phiên đăng nhập và cấp token JWT
            $token = JWTAuth::fromUser($user);
            $cookie = new Cookie('token', $token, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);

            return ApiResponse::success('Login successfully')->withCookie($cookie);
        } catch(ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
        catch(Exception $e) {
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return ApiResponse::error('some thing went wrong !!!');
        }
    }

    /**
     * Đăng nhập với facebook
     */
    public function redirectToFacebook()
    {
        $redirectUrl = Socialite::driver('facebook')
                                ->stateless()
                                ->redirect()
                                ->getTargetUrl();

        return ApiResponse::success('Facebook redirect URL generated successfully',data: [
            'url' => $redirectUrl
        ]);
    }

    /**
     * Xử lý call back facebook trả về và tạo phiên đăng nhập
     */

    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            $user = User::where('facebook_id', $facebookUser->getId())
                        ->orWhere('email', $facebookUser->getEmail())
                        ->first();

            // check nếu tài khoản này đã login bằng google rồi thì trả về lỗi duplicate email
            if($user && $user->google_id !== null) {
                throw new ApiException("Email {$user->email} đã được sử dụng bởi một phương thức đăng nhập khác " . ucfirst('facebook'), 409);
            }

            if(!$user) {
               // nếu ch código tạo mới tài khoản
                $user = User::create([
                    'name' => $facebookUser->getName(),
                    'email' => $facebookUser->getEmail(),
                    'password' => Hash::make(Str::random(16)),
                    'facebook_id' => $facebookUser->getId(),
                    'image' => $facebookUser->getAvatar(),
                    'role_id' => 4,
                    'is_active' => true
                ]);
            }

            // Tạo phiên đăng nhập và cấp token JWT
            $token = JWTAuth::fromUser($user);
            $cookie = new Cookie('token', $token, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);
            return ApiResponse::success('Login successfully')->withCookie($cookie);
        } catch(ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch(Exception $e) {
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return ApiResponse::error('some thing went wrong !!!');
        }
    }

    /**
     * Lấy thông tin user hiện tại.
     */
    public function user(): JsonResponse
    {
        try {
            $user = JWTAuth::user();
            $info = (new UserResource($user))->toArray(request());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return ApiResponse::success('Lấy thông tin thành công!!',data: $info);
    }

    /**
     * Cập nhật profile (name, phone, address, avatar).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('avatars', 'public');
            $data['image'] = $path;
        }
        $user->update($data);
        return (new UserResource($user))->response();
    }

    /**
     * Đổi mật khẩu.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! Hash::check($request->old_password, $user->password)) {
            return response()->json(['error' => 'Mật khẩu cũ không đúng.'], 422);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();
        return response()->json(['message' => 'Đổi mật khẩu thành công.']);
    }

    /**
     * Đăng xuất: vô hiệu token và xóa cookie.
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Không thể đăng xuất'], 500);
        }
        $cookie = new Cookie('token', '', now()->subMinute()->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);
        return response()->json(['message' => 'Đã đăng xuất'])->withCookie($cookie);
    }

    /**
     * Làm mới token JWT.
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh();
        } catch (JWTException $e) {
            return response()->json(['error' => 'Không thể refresh token'], 500);
        }
        $user = JWTAuth::authenticate($newToken);
        $cookie = new Cookie('token', $newToken, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);
        return (new UserResource($user))->response()->withCookie($cookie);
    }

    /**
     * Gửi lại mã OTP khi còn quota.
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        // 1. Validate email
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // 2. Lấy user
        $user = User::where('email', $request->email)->first();

        $now   = now();
        $start = $user->otp_sent_at ?? $now;
        $count = $user->otp_sent_count;

        // Nếu đã qua 1 giờ kể từ otp_sent_at, reset quota
        if ($start->diffInMinutes($now) >= 60) {
            $user->otp_sent_count = 0;
            $user->otp_sent_at    = $now;
            $count = 0;
        }

        // 3. Kiểm quota gửi lại
        if ($count >= 3) {
            $remaining = 60 - $start->diffInMinutes($now);
            return response()->json([
                'error' => "Bạn đã gửi quá 3 lần. Vui lòng thử lại sau {$remaining} phút."
            ], 429);
        }

        // 4. Sinh OTP mới & cập nhật TTL, quota
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp = $otp;
        $user->otp_expires_at = $now->addMinutes(5);
        $user->otp_sent_count++;
        if (!$user->otp_sent_at) {
            $user->otp_sent_at = $now;
        }
        $user->save();

        // 5. Gửi mail OTP
        $user->notify(new OtpNotification($otp, 5));

        return response()->json([
            'message' => 'OTP mới đã được gửi. Vui lòng kiểm tra email.'
        ], 200);
    }

    /**
     * Gửi mã OTP cho chức năng Quên mật khẩu.
     */
    public function sendForgotPasswordOtp(ForgotPasswordRequest $request): JsonResponse
    {
        // 1. Lấy user
        $user = User::where('email', $request->email)->first();

        $now   = now();
        $start = $user->otp_sent_at ?? $now;
        $count = $user->otp_sent_count;

        // 2. Reset quota nếu đã quá 1 giờ
        if ($start->diffInMinutes($now) >= 60) {
            $user->otp_sent_count = 0;
            $user->otp_sent_at    = $now;
            $count = 0;
        }

        // 3. Kiểm quota tối đa 3 lần
        if ($count >= 3) {
            $remaining = 60 - $start->diffInMinutes($now);
            return response()->json([
                'error' => "Bạn đã gửi quá 3 lần. Vui lòng thử lại sau {$remaining} phút."
            ], 429);
        }

        // 4. Sinh OTP mới, cập nhật TTL và quota
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp             = $otp;
        $user->otp_expires_at  = $now->addMinutes(5);
        $user->otp_sent_count++;
        if (! $user->otp_sent_at) {
            $user->otp_sent_at = $now;
        }
        $user->save();

        // 5. Gửi mail OTP
        $user->notify(new OtpNotification($otp, 5));

        return response()->json([
            'message' => 'OTP đã được gửi đến email. Vui lòng kiểm tra.'
        ], 200);
    }

    /**
 * Đặt lại mật khẩu bằng OTP.
 */
public function resetPasswordWithOtp(ResetPasswordWithOtpRequest $request): JsonResponse
{
    // Lấy user theo email
    $user = User::where('email', $request->email)->first();

    // Kiểm tra OTP và TTL
    if (! $user
        || $user->otp !== $request->otp
        || now()->gt($user->otp_expires_at)
    ) {
        return response()->json([
            'error' => 'OTP không hợp lệ hoặc đã hết hạn'
        ], 422);
    }

    // Cập nhật mật khẩu mới
    $user->password = Hash::make($request->new_password);

    // Xóa các trường OTP để không tái sử dụng
    $user->otp               = null;
    $user->otp_expires_at    = null;
    $user->otp_sent_count    = 0;
    $user->otp_sent_at       = null;

    $user->save();

    return response()->json([
        'message' => 'Đặt lại mật khẩu thành công.'
    ], 200);
}
}
