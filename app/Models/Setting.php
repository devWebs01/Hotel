<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'phone',
        'expire_time',

        'daily_price',
        'monthly_price',
    ];

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}
