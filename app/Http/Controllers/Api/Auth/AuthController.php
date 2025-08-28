<?php

namespace App\Http\Controllers\Api\Auth;

use App\Classes\Common;
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
use App\Services\SystemSettingService;
use Exception;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private SystemSettingService $settings) {}

    private function applyMailRuntime(): void
    {
        $mail = $this->settings->getEmailConfig(true);
        $this->settings->applyMailConfig($mail);
    }
    /**
     * Đăng ký khách hàng mới, lưu OTP và gửi mail.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            logger('--- Start Register ---');

            $data = $request->all();
            $data['password'] = Hash::make($data['password']);
            $data['role_id'] = User::where('name', 'customer')->first()->id ?? 4; // Lấy role_id của customer

            // Tạo user, is_active mặc định false
            $user = User::create(array_merge($data, ['is_active' => false]));

            // Sinh OTP 6 chữ số
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->otp = $otp;
            $user->otp_expires_at = now()->addMinutes(5);
            $user->otp_sent_count = 1;
            $user->otp_sent_at = now();
            $user->save();

            $this->applyMailRuntime();
            // Gửi mail OTP
            $user->notify(new OtpNotification($otp, 5));

            return ApiResponse::success('OTP đã được gửi về email của bạn. Vui lòng kiểm tra.');
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Xác thực OTP đăng ký, kích hoạt tài khoản và cấp JWT.
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {

        try {
            // Check OTP
            $user = User::where('email', $request->email)->first();
            if (!$user || $user->otp !== $request->otp || now()->gt($user->otp_expires_at)) {
                return ApiResponse::error('OTP không hợp lệ hoặc đã hết hạn!!', Response::HTTP_UNAUTHORIZED);
            }

            // Kích hoạt user
            $user->is_active = true;
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->otp_sent_count = 0;
            $user->save();

            // Cấp JWT
            $token = JWTAuth::fromUser($user);

            // $cookie = new Cookie(
            //     'token',
            //     $token,
            //     now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            //     '/',
            //     null,
            //     config('app.env') === 'production',
            //     true,
            //     false,
            //     Cookie::SAMESITE_LAX
            // );

            // return ApiResponse::success('Xác thực OTP thành công!!')->withCookie($cookie);

            $data = Common::respondWithToken($token);

            return ApiResponse::success('Xác thực OTP thành công!!', data: $data);
        } catch (JWTException $e) {
            logger('Log bug create auth token verify otp', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!');
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Đăng nhập bình thường: phát JWT và cookie.
     */
    public function login(LoginRequest $request)
    {

        try {
            $account = $request->input('account');
            $password = $request->input('password');

            // Tìm user theo email hoặc phone
            $user = User::where('email', $account)
                ->orWhere('phone', $account)
                ->first();

            // check tài khoản có hoạt động không
            if ($user && $user->is_active !== true) {
                throw new ApiException('Tài khoản không tồn tại, vui lòng liên hệ adminstrator', 401);
            }

            if (! $user || ! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
                throw new ApiException('Email/SĐT hoặc mật khẩu không đúng', 401);
            }

            // Tạo phiên đăng nhập
            $token = JWTAuth::fromUser($user);

            // $cookie = new Cookie(
            //     'token',
            //     $token,
            //     now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            //     '/',
            //     null,
            //     config('app.env') === 'production',
            //     true,
            //     false,
            //     Cookie::SAMESITE_LAX
            // );

            // $cookie = new Cookie(
            //     'token',
            //     $token,
            //     now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            //     '/',
            //     null,
            //     config('app.env') === 'production',
            //     true,
            //     false,
            //     Cookie::SAMESITE_LAX
            // );

            // $cookie = new Cookie(
            //     'token',
            //     $token,
            //     now()->addMinutes(config('jwt.ttl'))->getTimestamp(),
            //     '/',
            //     config('app.url'), // Cụ thể domain
            //     true, // Secure
            //     true, // HttpOnly
            //     false,
            //     Cookie::SAMESITE_NONE // <-- phải là None để gửi cross-origin
            // );

            // return ApiResponse::success('Đăng nhập thành công')->withCookie($cookie);

            $data = Common::respondWithToken($token);

            return ApiResponse::success('Đăng nhập thành công!!', data: $data);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (JWTException $e) {
            return ApiResponse::error('Có lỗi xảy ra');
        }
    }


    /**
     * Đăng nhập với google
     */
    public function redirectToGoogle()
    {
        try {
            $redirectUrl = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return ApiResponse::success('URL chuyển hướng Google đã được tạo thành công', data: [
                'url' => $redirectUrl
            ]);
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Xử lý call back google trả về và tạo phiên đăng nhập
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::with('role')
                ->where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())->first();

            // check nếu tài khoản này đã login bằng facebook rồi thì trả về lỗi duplicate email
            if ($user && $user->facebook_id !== null) {
                throw new ApiException("Email {$user->email} đã được sử dụng bởi một phương thức đăng nhập khác " . ucfirst('google'), 409);
            }

            if (!$user) {
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
            // Tạo phiên đăng nhập và cấp token JWT và check role để redirect
            $token = JWTAuth::fromUser($user);


            // $cookie = new Cookie('token', $token, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);

            // $hasManagementAccess = Common::hasRole($user->role->name, 'super-admin', 'admin', 'staff');
            // $redirectUrl = $hasManagementAccess ? env('ADMIN_URL') : env('FRONTEND_URL');

            $time_valid =  now()->addMinutes(config('jwt.ttl'))->getTimestamp();

            $redirectUrl = config('services.frontend_url.url') . '/authentication?token=' . $token . '&time_valid=' . $time_valid;

            // return redirect()->away(config('services.frontend_url'))->withCookie($cookie);
            return redirect()->away($redirectUrl);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return ApiResponse::error('Có lỗi xảy ra !!!');
        }
    }

    /**
     * Đăng nhập với facebook
     */
    public function redirectToFacebook()
    {
        try {
            $redirectUrl = Socialite::driver('facebook')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return ApiResponse::success('Facebook redirect URL generated successfully', data: [
                'url' => $redirectUrl
            ]);
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Xử lý call back facebook trả về và tạo phiên đăng nhập
     */

    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            $user = User::with('role')
                ->where('facebook_id', $facebookUser->getId())
                ->orWhere('email', $facebookUser->getEmail())
                ->first();

            // check nếu tài khoản này đã login bằng google rồi thì trả về lỗi duplicate email
            if ($user && $user->google_id !== null) {
                throw new ApiException("Email {$user->email} đã được sử dụng bởi một phương thức đăng nhập khác " . ucfirst('facebook'), 409);
            }

            if (!$user) {
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



            // $cookie = new Cookie('token', $token, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);

            // $hasManagementAccess = Common::hasRole($user->role->name, 'super-admin', 'admin', 'staff');
            // $redirectUrl = $hasManagementAccess ? env('ADMIN_URL') : env('FRONTEND_URL');

            $time_valid =  now()->addMinutes(config('jwt.ttl'))->getTimestamp();

            $redirectUrl = config('services.frontend_url.url') . '/authentication?token=' . $token . '&time_valid=' . $time_valid;

            // return redirect()->away(config('services.frontend_url'))->withCookie($cookie);
            return redirect()->away($redirectUrl);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (Exception $e) {
            logger('Log bug', [
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
    // public function user(): JsonResponse
    // {
    //     try {
    //         $user = JWTAuth::user();
    //         $info = (new UserResource($user))->toArray(request());
    //     } catch (JWTException $e) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }
    //     return ApiResponse::success('Lấy thông tin thành công!!',data: $info);
    // }

    public function user(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                throw new ApiException('Token không hợp lệ hoặc hết hạn!!', Response::HTTP_UNAUTHORIZED);
            }
            $info = (new UserResource($user))->toArray(request());
            return ApiResponse::success('Lấy thông tin thành công!!', data: $info);
        } catch (JWTException $e) {
            throw new ApiException('Không thể xác thực người dùng!!', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Cập nhật profile (name, phone, avatar).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $data = $request->validated();

        try {
            if ($request->hasFile('image')) {
                // Xoá ảnh cũ trên Cloudinary nếu có
                if (!empty($user->image)) {
                    $publicId = \App\Classes\Common::getCloudinaryPublicIdFromUrl($user->image);
                    if ($publicId) {
                        Common::deleteImageFromCloudinary($publicId);
                    }
                }

                // Upload ảnh mới lên Cloudinary
                $data['image'] = \App\Classes\Common::uploadImageToCloudinary(
                    $request->file('image'),
                    'egomall/avatars'
                );
            }

            $user->update($data);

            return ApiResponse::success('Lấy thông tin thành công!!', data: (new UserResource($user))->toArray(request()));
        } catch (\Exception $e) {
            logger('Log bug update profile', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Cập nhật hồ sơ thất bại!!', 500, [$e->getMessage()]);
        }
    }


    /**
     * Đổi mật khẩu.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth('api')->user();
            if (! Hash::check($request->old_password, $user->password)) {
                throw new ApiException('Mật khẩu cũ không đúng', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $user->password = Hash::make($request->new_password);
            $user->save();
            return ApiResponse::success('Đổi mật khẩu thành công.');
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Exception $e) {
            logger('Log bug change password', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Đăng xuất: vô hiệu token và xóa cookie.
     */
    // public function logout(): JsonResponse
    // {
    //     try {
    //         JWTAuth::invalidate();
    //     } catch (JWTException $e) {
    //         return response()->json(['error' => 'Không thể đăng xuất'], 500);
    //     }
    //     $cookie = new Cookie('token', '', now()->subMinute()->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);
    //     return response()->json(['message' => 'Đã đăng xuất'])->withCookie($cookie);
    // }
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate(); // ✅ parse token trước

            // $cookie = new Cookie(
            //     'token',
            //     '',
            //     now()->subMinute()->getTimestamp(),
            //     '/',
            //     null,
            //     config('app.env') === 'production',
            //     true,
            //     false,
            //     Cookie::SAMESITE_LAX
            // );

            return ApiResponse::success('Đã đăng xuất thành công!!');
        } catch (JWTException $e) {
            logger('Log bug sign out', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể đăng xuất!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Làm mới token JWT.
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();

            // $cookie = new Cookie('token', $newToken, now()->addMinutes(config('jwt.ttl'))->getTimestamp(), '/', null, config('app.env') === 'production', true, false, Cookie::SAMESITE_LAX);
            // return ApiResponse::success()->withCookie($cookie);


            $data = Common::respondWithToken($newToken);

            return ApiResponse::success('Làm mới token thành công!!', data: $data);
        } catch (JWTException $e) {
            logger('Log bug refresh token', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gửi lại mã OTP khi còn quota.
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            // Lấy user
            $user = User::where('email', $request->email)->first();

            // Kiểm tra user nếu đã xác thục rồi không cho resend lại OTP
            if ($user->is_active !== false) {
                throw new ApiException('Tài khoản đã được kích hoạt, không thể gửi lại OTP!!', Response::HTTP_CONFLICT);
            }

            $now   = now();
            $start = $user->otp_sent_at ?? $now;
            $count = $user->otp_sent_count;

            // Nếu đã qua 1 giờ kể từ otp_sent_at, reset quota
            if ($start->diffInMinutes($now) >= 5) {
                $user->otp_sent_count = 0;
                $user->otp_sent_at    = $now;
                $count = 0;
            }

            // 3. Kiểm quota gửi lại
            if ($count >= 3) {
                // $remaining = 60 - $start->diffInMinutes($now);
                throw new ApiException("Bạn đã gửi quá 3 lần. Vui lòng thử lại sau 5 phút.", Response::HTTP_TOO_MANY_REQUESTS);
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
            $this->applyMailRuntime();
            $user->notify(new OtpNotification($otp, 5));

            return ApiResponse::success('OTP mới đã được gửi. Vui lòng kiểm tra email.');
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (Exception $e) {
            logger('Log bug resend otp', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gửi mã OTP cho chức năng Quên mật khẩu.
     */
    public function sendForgotPasswordOtp(ForgotPasswordRequest $request): JsonResponse
    {
        try {
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
                throw new ApiException("Bạn đã gửi quá 3 lần. Vui lòng thử lại sau {$remaining} phút!!", Response::HTTP_TOO_MANY_REQUESTS);
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
            $this->applyMailRuntime();
            $user->notify(new OtpNotification($otp, 5));

            return ApiResponse::success('OTP đã được gửi đến email. Vui lòng kiểm tra.');
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Exception $e) {
            logger('Log bug send otp', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyResetOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || $user->otp !== $request->otp || now()->gt($user->otp_expires_at)) {
                throw new ApiException('OTP không hợp lệ hoặc đã hết hạn!!', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user->otp_verified = true;
            $user->save();

            return ApiResponse::success('Xác thực OTP thành công. Bạn có thể đặt lại mật khẩu.');
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Exception $e) {
            logger('Log bug verify otp', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setNewPassword(ResetPasswordWithOtpRequest $request): JsonResponse
    {

        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! $user->otp_verified) {
                throw new ApiException('Bạn chưa xác thực OTP!!', Response::HTTP_FORBIDDEN);
            }

            $user->password          = Hash::make($request->new_password);
            $user->otp               = null;
            $user->otp_expires_at    = null;
            $user->otp_sent_at       = null;
            $user->otp_sent_count    = 0;
            $user->otp_verified      = false;
            $user->save();

            return ApiResponse::success('Đổi mật khẩu thành công. Vui lòng đăng nhập lại.');
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Exception $e) {
            logger('Log bug set password', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
