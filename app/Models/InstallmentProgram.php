<?php

namespace App\Models;

use App\Helpers\FeaturesFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_id',
        'name',
        'interest_rate_per_year',
        'applicable_to',
        'description',
        'card_image',
        'features',
    ];

    protected $casts = [
        'interest_rate_per_year' => 'decimal:2',
    ];

    public const UPLOAD_FOLDER = 'upload/installment_programs/';

    public function getCardImagePathAttribute()
    {
        return $this->card_image ? asset(self::UPLOAD_FOLDER . $this->card_image) : null;
    }
    protected function features(): Attribute
    {
        return Attribute::make(
            get: fn($value) => FeaturesFormatter::normalize($value, false),
            set: fn($value) => json_encode(FeaturesFormatter::sanitize($value)),
        );
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
