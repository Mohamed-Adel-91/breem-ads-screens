<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'name', 'phone', 'email', 'payload'
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}

