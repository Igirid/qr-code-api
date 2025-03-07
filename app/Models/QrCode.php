<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class QrCode extends Model
{
    protected $fillable = ['code', 'status', 'expires_at'];

    protected $appends = ['qr_code'];

    protected function qrCode(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => asset("codes/{$attributes['code']}.svg")
        );
    }
}
