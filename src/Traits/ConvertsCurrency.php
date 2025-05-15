<?php
namespace faysal0x1\PaymentGateway\Traits;

use faysal0x1\PaymentGateway\Exceptions\PaymentException;

trait ConvertsCurrency
{
    protected function convertAmount(float $amount, string $fromCurrency, string $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rates = config('payment-gateway.currency_rates', []);
        
        if (!isset($rates[$fromCurrency][$toCurrency])) {
            throw new PaymentException("Currency conversion rate not available for $fromCurrency to $toCurrency");
        }

        return $amount * $rates[$fromCurrency][$toCurrency];
    }
}