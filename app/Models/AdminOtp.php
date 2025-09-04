<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminOtp extends Model
{
    use HasFactory;


    protected $table = 'admin_otps';

    protected $fillable = [
        'admin_id',
        'otp_code',
        'expires_at',
    ];

    protected $dates = [
        'expires_at',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
