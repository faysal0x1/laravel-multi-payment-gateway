<?php

namespace MultiPayment\Exceptions;

use Exception;

class PaymentException extends Exception
{
    /**
     * Create a new payment exception instance.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
