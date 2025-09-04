<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'full_name',
        'nickname',
        'email',
        'mobile',
        'image',
        'address',
        'password',
        'google_id',
        'facebook_id',
        'remember_token',
    ];
    protected $hidden = ['password', 'remember_token'];

    public const UPLOAD_FOLDER = 'upload/users/';

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
