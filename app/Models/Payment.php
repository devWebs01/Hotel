<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_date',
        'amount',
        'payment_method',
        'receipt',
        'status',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(booking::class);
    }
}