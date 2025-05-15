<?php

namespace MultiPayment\Exceptions;

class InvalidGatewayException extends PaymentException
{
    /**
     * Create a new invalid gateway exception instance.
     *
     * @param string $gateway
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($gateway, $code = 0, Exception $previous = null)
    {
        $message = "The payment gateway '{$gateway}' is not supported or invalid.";
        parent::__construct($message, $code, $previous);
    }
}
