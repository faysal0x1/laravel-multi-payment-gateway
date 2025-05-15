<?php
namespace faysal0x1\PaymentGateway\Exceptions;

use Exception;

class PaymentException extends Exception
{
    protected $code = 400;

    public static function gatewayNotActive(string $gateway): self
    {
        return new static("Payment gateway [{$gateway}] is not active");
    }

    public static function invalidCredentials(string $gateway): self
    {
        return new static("Invalid credentials for payment gateway [{$gateway}]");
    }

    public static function paymentFailed(string $message = 'Payment failed'): self
    {
        return new static($message);
    }

    public static function invalidVerification(): self
    {
        return new static('Payment verification failed');
    }
}