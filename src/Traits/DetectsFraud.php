<?php
namespace faysal0x1\PaymentGateway\Traits;

use faysal0x1\PaymentGateway\Exceptions\PaymentException;

trait DetectsFraud
{
    protected function checkForFraud(array $paymentData): bool
    {
        // Implement basic fraud checks
        $suspicious = false;
        
        // Check for unusually high amount
        if ($paymentData['amount'] > config('payment-gateway.fraud.max_amount', 100000)) {
            $suspicious = true;
        }
        
        // Check for high velocity (multiple payments in short time)
        $recentPayments = PaymentTransaction::where('customer_id', $paymentData['customer_id'] ?? null)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();
            
        if ($recentPayments > config('payment-gateway.fraud.max_payments_per_10min', 5)) {
            $suspicious = true;
        }
        
        return $suspicious;
    }
}