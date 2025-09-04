<?php

namespace App\Models;

use App\Enums\CashMethodEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Enums\ApplicableToEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id',
        'car_term_id',
        'first_color_id',
        'second_color_id',
        'insurance_programe_id',
        'insurance_programe_name',
        'branch_name',
        'payment_type',       // 1 cash, 2 installment
        'customer_type',      // 1 individual, 2 company
        'cash_method',
        'account_number',
        // Cash
        'cash_full_name',
        'cash_phone_number',
        'cash_individual_email',
        'cash_national_id',
        'cash_front_national_id_image',
        'cash_back_national_id_image',
        // Installment common
        'bank_id',
        'bank_name',
        'installment_program_id',
        'installment_program_name',
        'installment_duration',
        // Pricing
        'price',
        'reservation_amount',
        'interest_rate',
        'down_payment_percent',
        'down_payment_amount',
        'monthly_installment_amount',
        // Installment - Individual
        'installment_full_name',
        'installment_phone_number',
        'installment_individual_email',
        'installment_national_id',
        'installment_front_national_id_image',
        'installment_back_national_id_image',
        'installment_bank_statement',
        'installment_hr_letter',
        // Installment - Company
        'installment_company_name',
        'installment_legal_representative_phone_number',
        'installment_company_email',
        'installment_commercial_registration_number',
        'installment_commercial_registration_image',
        'installment_tax_card_number',
        'installment_tax_card_image',
        'installment_company_bank_statement',
        'agreed_terms',
        'reference_number',
        'status',
        'provider_order_reference',
        'provider_transaction_reference',
        'inventory_decremented'
    ];
    protected $casts = [
        'payment_type'   => PaymentTypeEnum::class,
        'cash_method'    => CashMethodEnum::class,
        'customer_type'  => ApplicableToEnum::class,
        'status'         => OrderStatusEnum::class,
        'agreed_terms'   => 'boolean',
        'price'               => 'integer',
        'reservation_amount'  => 'integer',
        'down_payment_amount' => 'integer',
        'down_payment_percent' => 'decimal:2',
        'inventory_decremented' => 'boolean',
    ];
    protected $attributes = [
        'status' => OrderStatusEnum::PENDING,
        'inventory_decremented' => false,
    ];
    public function bookingCarClone()
    {
        return $this->hasOne(BookingCarClone::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function term()
    {
        return $this->belongsTo(CarTerm::class, 'car_term_id');
    }
    public function firstColor()
    {
        return $this->belongsTo(Color::class, 'first_color_id');
    }
    public function secondColor()
    {
        return $this->belongsTo(Color::class, 'second_color_id');
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            $datePart = now()->timezone(config('app.timezone'))->format('Ymd');
            $modelName = null;
            if ($order->relationLoaded('term')) {
                $modelName = optional(optional($order->term)->model)->name;
            }
            if (!$modelName && $order->car_term_id) {
                $term = CarTerm::with('model:id,name')->find($order->car_term_id);
                $modelName = optional(optional($term)->model)->name;
            }
            $modelCode = $modelName
                ? Str::upper(Str::limit(preg_replace('/[^A-Za-z0-9]/', '', Str::slug($modelName, '')), 6, ''))
                : 'MODEL';
            if (empty($modelCode)) {
                $modelCode = 'MODEL';
            }
            if (empty($order->status)) {
                $order->status = OrderStatusEnum::PENDING;
            }
            do {
                $randomPart = Str::upper(Str::random(8));
                $referenceNumber = "REF-{$datePart}-{$modelCode}-{$randomPart}";
            } while (self::where('reference_number', $referenceNumber)->exists());
            $order->reference_number = $referenceNumber;
        });
    }
    public function getDisplayCustomerNameAttribute(): string
    {
        return $this->cash_full_name
            ?? $this->installment_full_name
            ?? $this->installment_company_name
            ?? $this->user?->full_name
            ?? '-';
    }
    public function getDisplayCustomerPhoneAttribute(): string
    {
        return $this->cash_phone_number
            ?? $this->installment_phone_number
            ?? $this->installment_legal_representative_phone_number
            ?? $this->user?->mobile
            ?? '-';
    }
}
