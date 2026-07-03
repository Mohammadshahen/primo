<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'password',
        'phone',
        'avatar',
        'is_admin',
        'phone_verified_at',
        'notification_offer',
        'notification_order',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verified_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_offer' => 'boolean',
            'notification_order' => 'boolean',
        ];
    }

    public function devices()
    {
        return $this->morphMany(Device::class, 'owner');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Register or update FCM token for a device.
     * Users support multiple devices.
     *
     * @param string $fcmToken
     * @return Device
     */
    public function registerDevice(string $fcmToken): Device
    {
        return Device::registerMultiDevice($this, $fcmToken);
    }

    /**
     * Get all FCM tokens for the user.
     *
     * @return \Illuminate\Support\Collection<string>
     */
    public function getFcmTokens()
    {
        return Device::getTokens($this);
    }

    /**
     * التحقق من أن رقم الهاتف مفعل
     */
    public function isPhoneVerified(): bool
    {
        return !is_null($this->phone_verified_at);
    }
}
