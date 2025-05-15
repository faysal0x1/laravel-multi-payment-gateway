# Payment Gateway Package Usage Guide

This guide provides detailed examples and usage patterns for the Multi-Payment Gateway package.

## Table of Contents
- [Basic Implementation](#basic-implementation)
- [Payment Processing](#payment-processing)
- [Payment Verification](#payment-verification)
- [Refund Processing](#refund-processing)
- [Webhook Handling](#webhook-handling)
- [Complete Implementation Example](#complete-implementation-example)

## Basic Implementation

### 1. Controller Setup

```php
<?php

namespace App\Http\Controllers;

use faysal0x1\PaymentGateway\Facades\PaymentGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
        ]);

        try {
            $payment = PaymentGateway::pay([
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'success_url' => route('payment.success'),
                'fail_url' => route('payment.fail'),
                'cancel_url' => route('payment.cancel'),
                'ipn_url' => route('payment.ipn'),
                'customer_id' => auth()->id(),
                'customer_name' => auth()->user()->name,
                'customer_email' => auth()->user()->email,
                'customer_phone' => auth()->user()->phone,
                'order_id' => 'ORDER-' . uniqid(),
                'product_name' => 'Product Name',
                'product_category' => 'Category',
                'product_profile' => 'general',
            ]);

            return redirect($payment['redirect_url']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

### 2. Route Setup

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/fail', [PaymentController::class, 'fail'])->name('payment.fail');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::post('/payment/ipn', [PaymentController::class, 'ipn'])->name('payment.ipn');
});
```

### 3. View Implementation

```php
<!-- resources/views/payment/form.blade.php -->
<form action="{{ route('payment.initiate') }}" method="POST">
    @csrf
    <div class="form-group">
        <label for="amount">Amount</label>
        <input type="number" name="amount" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="currency">Currency</label>
        <select name="currency" class="form-control" required>
            <option value="BDT">BDT</option>
            <option value="USD">USD</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Pay Now</button>
</form>
```

## Payment Processing

### 1. Using Default Gateway

```php
$payment = PaymentGateway::pay([
    'amount' => 1000,
    'currency' => 'BDT',
    'success_url' => route('payment.success'),
    'fail_url' => route('payment.fail'),
    'cancel_url' => route('payment.cancel'),
    'ipn_url' => route('payment.ipn'),
]);
```

### 2. Using Specific Gateway

```php
// Using bKash
$payment = PaymentGateway::gateway('bkash')->pay([
    'amount' => 1000,
    'currency' => 'BDT',
    'success_url' => route('payment.success'),
    'fail_url' => route('payment.fail'),
    'cancel_url' => route('payment.cancel'),
    'ipn_url' => route('payment.ipn'),
]);

// Using Nagad
$payment = PaymentGateway::gateway('nagad')->pay([
    'amount' => 1000,
    'currency' => 'BDT',
    'success_url' => route('payment.success'),
    'fail_url' => route('payment.fail'),
    'cancel_url' => route('payment.cancel'),
    'ipn_url' => route('payment.ipn'),
]);
```

## Payment Verification

### 1. Success Handler

```php
public function success(Request $request)
{
    try {
        $verification = PaymentGateway::verify([
            'transaction_id' => $request->tran_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);

        if ($verification['status'] === 'VALID') {
            // Update your database
            $transaction = PaymentTransaction::where('transaction_id', $request->tran_id)->first();
            $transaction->update([
                'status' => 'completed',
                'payment_details' => $verification
            ]);

            return redirect()->route('payment.success.page')->with('success', 'Payment successful!');
        }

        return redirect()->route('payment.fail.page')->with('error', 'Payment verification failed');
    } catch (\Exception $e) {
        return redirect()->route('payment.fail.page')->with('error', $e->getMessage());
    }
}
```

### 2. IPN Handler

```php
public function ipn(Request $request)
{
    try {
        $verification = PaymentGateway::verify([
            'transaction_id' => $request->tran_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);

        if ($verification['status'] === 'VALID') {
            // Update your database
            $transaction = PaymentTransaction::where('transaction_id', $request->tran_id)->first();
            $transaction->update([
                'status' => 'completed',
                'ipn_response' => $request->all()
            ]);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed'], 400);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
```

## Refund Processing

### 1. Refund Request

```php
public function refund(Request $request)
{
    $validated = $request->validate([
        'transaction_id' => 'required|string',
        'amount' => 'required|numeric',
        'reason' => 'required|string',
    ]);

    try {
        $refund = PaymentGateway::refund([
            'transaction_id' => $validated['transaction_id'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        if ($refund['status'] === 'success') {
            // Update your database
            $transaction = PaymentTransaction::where('transaction_id', $validated['transaction_id'])->first();
            $transaction->update([
                'status' => 'refunded',
                'refund_details' => $refund
            ]);

            return back()->with('success', 'Refund processed successfully');
        }

        return back()->with('error', 'Refund processing failed');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

## Webhook Handling

### 1. Webhook Controller

```php
<?php

namespace App\Http\Controllers;

use faysal0x1\PaymentGateway\Traits\VerifiesWebhooks;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    use VerifiesWebhooks;

    public function handle(Request $request)
    {
        try {
            // Verify webhook signature
            $this->verifyWebhookSignature($request, config('payment-gateway.webhook_secret'));

            // Process webhook data
            $data = $request->all();
            
            // Update transaction status
            $transaction = PaymentTransaction::where('transaction_id', $data['transaction_id'])->first();
            if ($transaction) {
                $transaction->update([
                    'status' => $data['status'],
                    'webhook_data' => $data
                ]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
```

## Complete Implementation Example

### 1. Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use faysal0x1\PaymentGateway\Facades\PaymentGateway;

class PaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('payment-service', function ($app) {
            return new PaymentService($app);
        });
    }
}
```

### 2. Payment Service

```php
<?php

namespace App\Services;

use faysal0x1\PaymentGateway\Facades\PaymentGateway;
use faysal0x1\PaymentGateway\Traits\DetectsFraud;
use faysal0x1\PaymentGateway\Traits\ConvertsCurrency;

class PaymentService
{
    use DetectsFraud, ConvertsCurrency;

    public function processPayment(array $data)
    {
        // Check for fraud
        if ($this->checkForFraud($data)) {
            throw new \Exception('Suspicious payment detected');
        }

        // Convert currency if needed
        if ($data['currency'] !== 'BDT') {
            $data['amount'] = $this->convertAmount(
                $data['amount'],
                $data['currency'],
                'BDT'
            );
            $data['currency'] = 'BDT';
        }

        // Process payment
        return PaymentGateway::pay($data);
    }

    public function verifyPayment(array $data)
    {
        return PaymentGateway::verify($data);
    }

    public function processRefund(array $data)
    {
        return PaymentGateway::refund($data);
    }
}
```

### 3. Usage in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
        ]);

        try {
            $payment = $this->paymentService->processPayment([
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'success_url' => route('payment.success'),
                'fail_url' => route('payment.fail'),
                'cancel_url' => route('payment.cancel'),
                'ipn_url' => route('payment.ipn'),
                'customer_id' => auth()->id(),
                'customer_name' => auth()->user()->name,
                'customer_email' => auth()->user()->email,
                'customer_phone' => auth()->user()->phone,
            ]);

            return redirect($payment['redirect_url']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

### 4. Database Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name');
            $table->string('transaction_id')->unique();
            $table->string('order_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->json('payment_details')->nullable();
            $table->json('ipn_response')->nullable();
            $table->json('refund_details')->nullable();
            $table->json('webhook_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
}
```

This documentation provides a complete guide to implementing the payment gateway package in your Laravel application. Each section includes detailed examples and best practices for handling payments, verifications, refunds, and webhooks.

For more information about specific features or customization options, please refer to the main README.md file or the package documentation. 