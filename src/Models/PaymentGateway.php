<?php
// src/Models/PaymentGateway.php
namespace faysal0x1\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'credentials',
        'test_mode',
        'additional_parameters'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'test_mode' => 'boolean',
        'credentials' => 'array',
        'additional_parameters' => 'array'
    ];

    public static function getGateway(string $name)
    {
        return static::where('name', $name)->where('is_active', true)->first();
    }
}