<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;  // thêm use này

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Các trường cho phép gán hàng loạt (mass assignable).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'google_id',
        'facebook_id',
        'address',
        'image',
        'role_id',
        'is_active',
        'otp',
        'expires_at',
        'otp_sent_count',
        'otp_sent_at',
    ];

    /**
     * Các trường sẽ không hiển thị trong JSON output.
     */
    protected $hidden = [
        'password',
        'otp',
    ];

    /**
     * Các kiểu dữ liệu (casts) cho các cột.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'expires_at'        => 'datetime',
        'is_active'         => 'boolean',
        'otp_expires_at' => 'datetime',
        'otp_sent_at'    => 'datetime',
    ];

    public function setIsActiveAttribute($value)
    {
        $this->attributes['is_active'] = $value;

        if ($value && (!array_key_exists('email_verified_at', $this->attributes) || is_null($this->attributes['email_verified_at']))) {
            $this->attributes['email_verified_at'] = Carbon::now();
        }
    }

    /**
     * Phương thức bắt buộc của JWTSubject:
     * Trả về khóa chính để ký token.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Phương thức bắt buộc của JWTSubject:
     * Trả về mảng custom claims (nếu cần).
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'author_id');
    }

    public function wishLists()
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }
}