<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\SystemSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;

class SystemSettingService
{
    // Cache keys
    private const CACHE_ALL   = 'settings:all';
    private const CACHE_GROUP = 'settings:group:'; // + group name

    // Key nhạy cảm
    private const SENSITIVE_KEYS = ['email_password'];

    // Nhóm email để apply runtime
    private const EMAIL_GROUP_KEYS = [
        'email_from_name',
        'email_from_address',
        'email_driver',
        'email_host',
        'email_port',
        'email_username',
        'email_password',
        'email_encryption',
    ];

    /**
     * Lấy danh sách settings (có thể theo group).
     * Trả về mảng item gồm: key, value (password sẽ mask), type, group, label, description, updated_at
     */
    public function list(?string $group = null): Collection
    {
        try {
            $cacheKey = $group ? self::CACHE_GROUP . $group : self::CACHE_ALL;

            return Cache::rememberForever($cacheKey, function () use ($group) {
                $q = SystemSetting::query();

                if ($group) {
                    $q->where('setting_group', $group)
                        ->orderBy('setting_key');
                } else {
                    $q->orderBy('setting_group')
                        ->orderBy('setting_key');
                }

                return $q->get(); // trả Eloquent Collection
            });
        } catch (\Exception $e) {
            logger('Log bug settings.list', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);

            throw new ApiException('Không thể lấy cấu hình hệ thống!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy map key => value (mặc định mask password). Dùng khi FE cần chỉ giá trị.
     */
    public function getKeyValue(?string $group = null, bool $maskSensitive = true): array
    {
        $rows = $this->list($group);
        $map  = [];
        foreach ($rows as $row) {
            $v = $row['setting_value'];
            if ($maskSensitive && $row['setting_type'] === 'password') {
                $v = '******';
            }
            $map[$row['setting_key']] = $v;
        }
        return $map;
    }

    /**
     * Update nhiều keys một lần.
     * - Chỉ update keys tồn tại trong DB
     * - Validate theo setting_type
     * - Cast trước khi lưu (boolean/number/json...)
     * - Password: rỗng/"******" => giữ nguyên; có giá trị => encrypt
     * - Chỉ clear cache & apply mail khi thực sự có thay đổi
     *
     * @param array $payload  Dữ liệu update (key => value)
     * @param bool  $allowNull Cho phép set null (default: false)
     * @return array Danh sách key đã thay đổi
     */
    public function update(array $payload, bool $allowNull = false): array
    {
        // Bảo vệ input quá lớn
        if (count($payload) > 100) {
            throw new ApiException('Payload quá lớn!', Response::HTTP_BAD_REQUEST);
        }

        // Chuẩn hoá: bỏ key null nếu không cho phép set null
        if (!$allowNull) {
            $payload = array_filter($payload, fn($v) => !is_null($v));
        }

        // Trim chuỗi
        foreach ($payload as $k => $v) {
            if (is_string($v)) {
                $payload[$k] = trim($v);
            }
        }

        if (empty($payload)) {
            return [];
        }

        try {
            return DB::transaction(function () use ($payload) {
                // Lấy các setting hiện có theo danh sách key gửi lên
                $settings = SystemSetting::query()
                    ->whereIn('setting_key', array_keys($payload))
                    ->get()
                    ->keyBy('setting_key');

                // Key không tồn tại
                $unknown = array_values(array_diff(array_keys($payload), $settings->keys()->toArray()));
                if (!empty($unknown)) {
                    throw new ApiException('Khoá cấu hình không hợp lệ: ' . implode(', ', $unknown), Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $changedKeys   = [];
                $touchedGroups = [];

                foreach ($payload as $key => $inputVal) {
                    /** @var SystemSetting $setting */
                    $setting = $settings[$key];

                    // Password: nếu gửi '' hoặc '******' => bỏ qua, giữ nguyên
                    if ($setting->setting_type === 'password' && ($inputVal === '' || $inputVal === '******')) {
                        continue;
                    }

                    // Validate theo type (null hợp lệ khi allowNull hoặc type cho phép)
                    $this->validateByType($setting->setting_type, $inputVal, $setting->setting_label ?? $setting->setting_key);

                    // Cast + Encrypt nếu cần
                    $toSave = $this->castForStore($setting->setting_type, $inputVal, $setting->setting_key);

                    // Không ghi nếu không đổi (so sánh chuỗi để thống nhất)
                    if ((string)$toSave === (string)$setting->setting_value) {
                        continue;
                    }

                    $before = $setting->setting_value;
                    $setting->setting_value = $toSave;
                    $setting->save();

                    // Audit log (mask nếu nhạy cảm)
                    $masked = $this->isSensitive($setting);
                    logger('SystemSetting updated', [
                        'key'        => $key,
                        'group'      => $setting->setting_group,
                        'before'     => $masked ? '[MASKED]' : $before,
                        'after'      => $masked ? '[MASKED]' : $toSave,
                        'by_user_id' => optional(auth('api')->user())->id,
                    ]);

                    $changedKeys[] = $key;
                    $touchedGroups[$setting->setting_group] = true;
                }

                // Không có gì đổi → thoát sớm, không clear cache
                if (empty($changedKeys)) {
                    return [];
                }

                // Clear cache đúng phạm vi
                Cache::forget(self::CACHE_ALL);
                foreach (array_keys($touchedGroups) as $g) {
                    Cache::forget(self::CACHE_GROUP . $g);
                }

                // Nếu có thay đổi mail settings => apply runtime ngay
                if ($this->containsEmailKeys($changedKeys)) {
                    $mail = $this->getEmailConfig(true); // decrypt password
                    $this->applyMailConfig($mail);
                }

                return $changedKeys;
            });
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            logger('Log bug settings.update', [
                // tránh log full payload (có thể chứa secret)
                'keys'         => array_keys($payload),
                'error_message' => $e->getMessage(),
                'error_file'   => $e->getFile(),
                'error_line'   => $e->getLine(),
                'stack_trace'  => $e->getTraceAsString(),
            ]);
            throw new ApiException('Không thể cập nhật cấu hình hệ thống!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Lấy cấu hình email dạng mảng để apply vào config runtime.
     * @param bool $decryptPassword true => trả password đã decrypt
     */
    public function getEmailConfig(bool $decryptPassword = false): array
    {
        try {
            $rows = Cache::rememberForever(self::CACHE_GROUP . 'email', function () {
                return SystemSetting::query()
                    ->where('setting_group', 'email')
                    ->get()
                    ->keyBy('setting_key');
            });

            // Lấy từ DB
            $getDb = function (string $k, $default = null) use ($rows) {
                return isset($rows[$k]) ? $rows[$k]->setting_value : $default;
            };

            $password = $getDb('email_password');
            if ($decryptPassword && $password) {
                try {
                    $password = Crypt::decryptString($password);
                } catch (\Throwable $e) {
                    $password = null; // nếu decrypt fail → fallback .env
                }
            }

            // Lấy fallback từ .env
            $envFallback = [
                'email_from_name'    => config('mail.from.name'),
                'email_from_address' => config('mail.from.address'),
                'email_driver'       => config('mail.default'),
                'email_host'         => config('mail.mailers.smtp.host'),
                'email_port'         => config('mail.mailers.smtp.port'),
                'email_username'     => config('mail.mailers.smtp.username'),
                'email_password'     => config('mail.mailers.smtp.password'),
                'email_encryption'   => config('mail.mailers.smtp.encryption'),
            ];

            // Merge: ưu tiên DB, fallback từ .env nếu thiếu/empty
            $final = [
                'email_from_name'    => $getDb('email_from_name') ?: $envFallback['email_from_name'],
                'email_from_address' => $getDb('email_from_address') ?: $envFallback['email_from_address'],
                'email_driver'       => $getDb('email_driver') ?: $envFallback['email_driver'],
                'email_host'         => $getDb('email_host') ?: $envFallback['email_host'],
                'email_port'         => (int)($getDb('email_port') ?: $envFallback['email_port']),
                'email_username'     => $getDb('email_username') ?: $envFallback['email_username'],
                'email_password'     => $password ?: $envFallback['email_password'],
                'email_encryption'   => $getDb('email_encryption') ?: $envFallback['email_encryption'],
            ];

            return $final;
        } catch (\Exception $e) {
            logger('Log bug settings.getEmailConfig', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw new ApiException('Không thể lấy cấu hình email!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Áp dụng cấu hình email vào config() runtime (không đụng .env)
     */
    public function applyMailConfig(array $mail): void
    {
        try {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host',       $mail['email_host'] ?? 'smtp.gmail.com');
            Config::set('mail.mailers.smtp.port',       (int)($mail['email_port'] ?? 587));
            Config::set('mail.mailers.smtp.username',   $mail['email_username'] ?? null);
            Config::set('mail.mailers.smtp.password',   $mail['email_password'] ?? null);
            Config::set('mail.mailers.smtp.encryption', $mail['email_encryption'] ?? 'tls');
            Config::set('mail.from.address',            $mail['email_from_address'] ?? null);
            Config::set('mail.from.name',               $mail['email_from_name'] ?? config('app.name'));
        } catch (\Exception $e) {
            logger('Log bug settings.applyMailConfig', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw new ApiException('Không thể áp dụng cấu hình email!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* ========================= Helpers ========================= */

    private function isSensitive(SystemSetting $s): bool
    {
        return $s->setting_type === 'password' || in_array($s->setting_key, self::SENSITIVE_KEYS, true);
    }

    private function containsEmailKeys(array $keys): bool
    {
        foreach ($keys as $k) {
            if (in_array($k, self::EMAIL_GROUP_KEYS, true)) {
                return true;
            }
        }
        return false;
    }

    private function validateByType(string $type, $value, string $label = 'Giá trị'): void
    {
        $rules = match ($type) {
            'string'   => 'nullable|string|max:255',
            'text'     => 'nullable|string',
            'boolean'  => 'required|boolean',
            'number'   => 'required|numeric',
            'email'    => 'required|email',
            'url'      => 'required|url',
            'image'    => 'nullable|url', // FE upload Cloudinary -> gửi URL
            'password' => 'nullable|string|min:8',
            'json'     => 'nullable|json',
            default    => 'nullable',
        };

        $v = Validator::make(['value' => $value], ['value' => $rules], [], ['value' => $label]);
        if ($v->fails()) {
            throw new ApiException($v->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    private function castForStore(string $type, $value, string $key)
    {
        switch ($type) {
            case 'boolean':
                // chấp nhận true/false, "true"/"false", 1/0, "1"/"0"
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

            case 'number':
                // luôn lưu dạng số (string hoá)
                return (string) (is_numeric($value) ? $value + 0 : $value);

            case 'json':
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                // đã validate json, chuẩn hoá lại format
                $arr = json_decode((string)$value, true);
                return json_encode($arr, JSON_UNESCAPED_UNICODE);

            case 'password':
                // encrypt string, không lưu rỗng
                if ($value === null || $value === '') {
                    return null;
                }
                // Nếu user cố dán ****** thì coi như bỏ qua (đã xử lý trên), tới đây là real new password
                return Crypt::encryptString((string)$value);

            default:
                // string, text, email, url, image...
                return (string) $value;
        }
    }
}