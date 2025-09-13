<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable implements AuthenticatableContract
{
    use HasFactory, Notifiable, Authorizable, HasRoles;
    protected $guard_name = 'admin';
    protected $table = 'admins';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'mobile',
        'profile_picture',
    ];

    public const UPLOAD_FOLDER = 'upload/admins/';
    public function setPasswordAttribute(string $input): void
    {
        $this->attributes['password'] = Hash::make($input);
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
