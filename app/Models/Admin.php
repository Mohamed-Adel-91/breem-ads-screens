<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

class Admin extends Authenticatable implements AuthenticatableContract
{
    use HasFactory;

    protected $table = 'admins';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'mobile',
        'profile_picture',
        'role',
    ];

    public const UPLOAD_FOLDER = 'upload/admins/';
    public function setPasswordAttribute(string $input): void
    {
        $this->attributes['password'] = Hash::make($input);
    }

    public function isRole(...$roles)
    {
        return in_array($this->role, $roles);
    }
    public function getImagePathAttribute()
    {
        return $this->profile_picture ? asset(self::UPLOAD_FOLDER . $this->profile_picture) : null;
    }

    public function adminOtp()
    {
        return $this->hasOne(AdminOtp::class);
    }
}
