<?php
namespace faysal0x1\PaymentGateway\Exceptions;

use Exception;

class InvalidGatewayException extends Exception
{
    protected $code = 404;

    public static function notFound(string $gateway): self
    {
        return new static("Payment gateway [{$gateway}] not found");
    }

    public static function driverNotFound(string $driver): self
    {
        return new static("Payment gateway driver [{$driver}] not found");
    }

    public static function notConfigured(string $gateway): self
    {
        return new static("Payment gateway [{$gateway}] is not configured");
    }
}