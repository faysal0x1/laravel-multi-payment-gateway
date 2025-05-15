# Multi-Payment Gateway Package

A powerful and flexible Laravel package for integrating multiple payment gateways into your application. This package provides a unified interface for handling payments through various payment gateways including SSLCommerz, bKash, and Nagad.

## Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Advanced Usage](#advanced-usage)
- [Payment Gateway Integration](#payment-gateway-integration)
- [Security Features](#security-features)
- [Admin Interface](#admin-interface)
- [Testing](#testing)
- [Error Handling](#error-handling)
- [Contributing](#contributing)
- [License](#license)

## Features

### Core Features
- 🚀 Multiple payment gateway support
- 🔄 Unified payment processing interface
- 💱 Multi-currency support with conversion
- 🔒 Secure credential management
- 📊 Transaction tracking and logging
- 🛡️ Built-in fraud detection
- 🔔 Webhook handling
- 📱 Admin dashboard for gateway management

### Supported Gateways
- SSLCommerz
- bKash
- Nagad

## Requirements

- PHP >= 8.2
- Laravel >= 12.0
- Composer

## Installation

1. Install the package via Composer:
```bash
composer require faysal0x1/payment-gateway
```

2. Publish the configuration and migration files:
```bash
php artisan vendor:publish --tag=payment-gateway-config
php artisan vendor:publish --tag=payment-gateway-migrations
```

3. Run the migrations:
```bash
php artisan migrate
```

## Configuration

### Environment Variables
Add the following variables to your `.env` file:

```env
# Default Gateway
PAYMENT_DEFAULT_GATEWAY=sslcommerz

# SSLCommerz Configuration
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=your_store_password
SSLCOMMERZ_SANDBOX=true

# bKash Configuration
BKASH_APP_KEY=your_app_key
BKASH_APP_SECRET=your_app_secret
BKASH_USERNAME=your_username
BKASH_PASSWORD=your_password
BKASH_SANDBOX=true

# Nagad Configuration
NAGAD_MERCHANT_ID=your_merchant_id
NAGAD_MERCHANT_NUMBER=your_merchant_number
NAGAD_PRIVATE_KEY=your_private_key
NAGAD_SANDBOX=true
```

### Configuration File
The package configuration file (`config/payment-gateway.php`) contains settings for each payment gateway:

```php
return [
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'sslcommerz'),
    
    'gateways' => [
        'sslcommerz' => [
            'driver' => \faysal0x1\PaymentGateway\Drivers\SSLCommerz::class,
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        ],
        // Other gateway configurations...
    ],
];
```

## Basic Usage

### Process a Payment
```php
use faysal0x1\PaymentGateway\Facades\PaymentGateway;

$payment = PaymentGateway::pay([
    'amount' => 1000,
    'currency' => 'BDT',
    'success_url' => route('payment.success'),
    'fail_url' => route('payment.fail'),
    'cancel_url' => route('payment.cancel'),
    'ipn_url' => route('payment.ipn'),
    'customer_id' => auth()->id(),
    'customer_name' => auth()->user()->name,
    'customer_email' => auth()->user()->email,
    'customer_phone' => auth()->user()->phone,
]);
```

### Verify a Payment
```php
$verification = PaymentGateway::verify([
    'transaction_id' => 'TRX123456',
]);
```

### Process a Refund
```php
$refund = PaymentGateway::refund([
    'transaction_id' => 'TRX123456',
    'amount' => 1000,
    'reason' => 'Customer request',
]);
```

## Advanced Usage

### Using Specific Gateway
```php
// Use bKash gateway
$payment = PaymentGateway::gateway('bkash')->pay([
    'amount' => 1000,
    'currency' => 'BDT',
    'success_url' => route('payment.success'),
    'fail_url' => route('payment.fail'),
    'cancel_url' => route('payment.cancel'),
    'ipn_url' => route('payment.ipn'),
]);
```

### Currency Conversion
```php
use faysal0x1\PaymentGateway\Traits\ConvertsCurrency;

class PaymentService
{
    use ConvertsCurrency;

    public function processPayment($amount, $fromCurrency, $toCurrency)
    {
        $convertedAmount = $this->convertAmount($amount, $fromCurrency, $toCurrency);
        // Process payment with converted amount
    }
}
```

### Fraud Detection
```php
use faysal0x1\PaymentGateway\Traits\DetectsFraud;

class PaymentService
{
    use DetectsFraud;

    public function processPayment($paymentData)
    {
        if ($this->checkForFraud($paymentData)) {
            throw new PaymentException('Suspicious payment detected');
        }
        // Process payment
    }
}
```

### Webhook Verification
```php
use faysal0x1\PaymentGateway\Traits\VerifiesWebhooks;

class PaymentController extends Controller
{
    use VerifiesWebhooks;

    public function handleWebhook(Request $request)
    {
        $this->verifyWebhookSignature($request, config('payment-gateway.webhook_secret'));
        // Process webhook
    }
}
```

## Payment Gateway Integration

### Adding a New Gateway

1. Create a new driver class:
```php
namespace faysal0x1\PaymentGateway\Drivers;

class NewGateway extends AbstractDriver
{
    protected function setCredentials()
    {
        $this->credentials = [
            'api_key' => $this->getCredential('api_key'),
            'api_secret' => $this->getCredential('api_secret'),
        ];
    }

    public function pay(array $data)
    {
        // Implement payment logic
    }

    public function verify(array $data)
    {
        // Implement verification logic
    }

    public function refund(array $data)
    {
        // Implement refund logic
    }

    public function getGatewayName()
    {
        return 'new_gateway';
    }
}
```

2. Add gateway configuration:
```php
'gateways' => [
    'new_gateway' => [
        'driver' => \faysal0x1\PaymentGateway\Drivers\NewGateway::class,
        'api_key' => env('NEW_GATEWAY_API_KEY'),
        'api_secret' => env('NEW_GATEWAY_API_SECRET'),
    ],
],
```

## Security Features

### Credential Management
- Credentials are stored securely in the database
- Environment variables for sensitive data
- Encryption for stored credentials

### Fraud Prevention
- Amount-based fraud detection
- Velocity checking
- IP-based restrictions
- Suspicious pattern detection

### Webhook Security
- Signature verification
- IP whitelisting
- Request validation
- Secure data transmission

## Admin Interface

Generate CRUD interface for payment gateway management:
```bash
php artisan payment:make-crud
```

This will create:
- Controller
- Views
- Routes
- Form validation

## Testing

### Test Gateway Connection
```bash
php artisan payment:test {gateway}
```

### Writing Tests
```php
use faysal0x1\PaymentGateway\Facades\PaymentGateway;

class PaymentTest extends TestCase
{
    public function test_payment_processing()
    {
        $payment = PaymentGateway::pay([
            'amount' => 1000,
            'currency' => 'BDT',
        ]);

        $this->assertNotNull($payment);
        $this->assertArrayHasKey('transaction_id', $payment);
    }
}
```

## Error Handling

### Custom Exceptions
```php
use faysal0x1\PaymentGateway\Exceptions\PaymentException;
use faysal0x1\PaymentGateway\Exceptions\InvalidGatewayException;

try {
    $payment = PaymentGateway::pay($data);
} catch (PaymentException $e) {
    // Handle payment error
} catch (InvalidGatewayException $e) {
    // Handle gateway error
}
```

### Error Logging
```php
Log::error('Payment failed', [
    'gateway' => 'sslcommerz',
    'error' => $e->getMessage(),
    'data' => $data
]);
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

### Development Setup
```bash
composer install
php artisan test
```

## License

This package is open-sourced software licensed under the MIT license.

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.

---

Made with ❤️ by Faysal Rahman
