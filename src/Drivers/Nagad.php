<?php

namespace MultiPayment\Drivers;

use MultiPayment\Contracts\PaymentGatewayInterface;
use MultiPayment\Exceptions\PaymentException;

class Nagad implements PaymentGatewayInterface
{
    protected $config;
    protected $merchantId;
    protected $merchantKey;
    protected $sandbox;

    /**
     * Initialize Nagad payment gateway
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->merchantId = $config['merchant_id'] ?? null;
        $this->merchantKey = $config['merchant_key'] ?? null;
        $this->sandbox = $config['sandbox'] ?? false;
    }

    /**
     * Process payment
     *
     * @param array $data
     * @return array
     * @throws PaymentException
     */
    public function processPayment(array $data)
    {
        try {
            // Implement Nagad payment processing logic here
            // This is a placeholder implementation
            return [
                'success' => true,
                'transaction_id' => uniqid('nagad_'),
                'message' => 'Payment processed successfully'
            ];
        } catch (\Exception $e) {
            throw new PaymentException('Nagad payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment
     *
     * @param string $transactionId
     * @return array
     * @throws PaymentException
     */
    public function verifyPayment($transactionId)
    {
        try {
            // Implement Nagad payment verification logic here
            // This is a placeholder implementation
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $transactionId
            ];
        } catch (\Exception $e) {
            throw new PaymentException('Nagad payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getGatewayName()
    {
        return 'Nagad';
    }
}
