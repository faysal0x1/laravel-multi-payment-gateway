<?php

namespace Tests\Unit;

use Tests\TestCase;
use faysal0x1\PaymentGateway\Facades\PaymentGateway;
use faysal0x1\PaymentGateway\Models\PaymentGateway as PaymentGatewayModel;
use faysal0x1\PaymentGateway\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test payment gateway
        PaymentGatewayModel::create([
            'name' => 'SSLCommerz',
            'driver' => 'sslcommerz',
            'credentials' => [
                'store_id' => 'test_store_id',
                'store_password' => 'test_password',
                'sandbox' => true
            ],
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_resolve_default_gateway()
    {
        $gateway = PaymentGateway::getDefaultGateway();
        $this->assertEquals('SSLCommerz', $gateway->name);
    }

    /** @test */
    public function it_can_resolve_specific_gateway()
    {
        $gateway = PaymentGateway::gateway('sslcommerz');
        $this->assertInstanceOf(\faysal0x1\PaymentGateway\Drivers\SSLCommerz::class, $gateway);
    }

    /** @test */
    public function it_throws_exception_for_invalid_gateway()
    {
        $this->expectException(\faysal0x1\PaymentGateway\Exceptions\InvalidGatewayException::class);
        PaymentGateway::gateway('invalid_gateway');
    }

    /** @test */
    public function it_can_initiate_payment()
    {
        $payment = PaymentGateway::pay([
            'amount' => 100,
            'currency' => 'BDT',
            'order_id' => 'TEST-123',
            'success_url' => 'http://example.com/success',
            'fail_url' => 'http://example.com/fail',
            'cancel_url' => 'http://example.com/cancel',
            'ipn_url' => 'http://example.com/ipn',
            'customer_id' => 1,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '1234567890'
        ]);

        $this->assertArrayHasKey('redirect_url', $payment);
        $this->assertArrayHasKey('transaction_id', $payment);
    }

    /** @test */
    public function it_can_verify_payment()
    {
        // Create a test transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TEST-TRANS-123',
            'gateway_name' => 'SSLCommerz',
            'amount' => 100,
            'currency' => 'BDT',
            'status' => 'pending'
        ]);

        $verification = PaymentGateway::verify('TEST-TRANS-123');
        $this->assertIsArray($verification);
        $this->assertArrayHasKey('status', $verification);
    }

    /** @test */
    public function it_can_process_refund()
    {
        // Create a test transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TEST-TRANS-123',
            'gateway_name' => 'SSLCommerz',
            'amount' => 100,
            'currency' => 'BDT',
            'status' => 'completed'
        ]);

        $refund = PaymentGateway::refund([
            'transaction_id' => 'TEST-TRANS-123',
            'amount' => 100,
            'reason' => 'Test refund'
        ]);

        $this->assertIsArray($refund);
        $this->assertArrayHasKey('status', $refund);
    }

    /** @test */
    public function it_validates_required_payment_parameters()
    {
        $this->expectException(\faysal0x1\PaymentGateway\Exceptions\PaymentException::class);
        
        PaymentGateway::pay([
            'amount' => 100,
            // Missing required parameters
        ]);
    }

    /** @test */
    public function it_handles_payment_verification_errors()
    {
        $this->expectException(\faysal0x1\PaymentGateway\Exceptions\PaymentException::class);
        
        PaymentGateway::verify('INVALID-TRANSACTION-ID');
    }

    /** @test */
    public function it_handles_refund_errors()
    {
        $this->expectException(\faysal0x1\PaymentGateway\Exceptions\PaymentException::class);
        
        PaymentGateway::refund([
            'transaction_id' => 'INVALID-TRANSACTION-ID',
            'amount' => 100
        ]);
    }
} 