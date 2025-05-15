<?php
namespace faysal0x1\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'gateway_name',
        'transaction_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'payment_details',
        'ipn_response',
        'customer_id'
    ];

    protected $casts = [
        'payment_details' => 'array',
        'ipn_response' => 'array'
    ];
}