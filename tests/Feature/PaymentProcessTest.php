<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use faysal0x1\PaymentGateway\Facades\PaymentGateway;
use faysal0x1\PaymentGateway\Models\PaymentGateway as PaymentGatewayModel;
use faysal0x1\PaymentGateway\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class PaymentProcessTest extends TestCase
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

        // Create test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_order_and_initiate_payment()
    {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => 'PROD-1',
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                    'unit_price' => 100
                ]
            ],
            'billing_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address' => '123 Test St',
                'city' => 'Test City',
                'postal_code' => '12345'
            ],
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address' => '123 Test St',
                'city' => 'Test City',
                'postal_code' => '12345'
            ],
            'payment_gateway' => 'sslcommerz'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'order_number',
                    'total_amount',
                    'status'
                ],
                'payment_url'
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => 'PROD-1',
            'quantity' => 2,
            'unit_price' => 100
        ]);
    }

    /** @test */
    public function it_validates_required_order_fields()
    {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [],
            'billing_address' => [],
            'shipping_address' => [],
            'payment_gateway' => 'invalid_gateway'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items',
                'billing_address',
                'shipping_address',
                'payment_gateway'
            ]);
    }

    /** @test */
    public function it_handles_payment_success_callback()
    {
        // Create test order
        $order = Order::create([
            'order_number' => 'ORD-TEST-123',
            'user_id' => $this->user->id,
            'total_amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'billing_address' => [],
            'shipping_address' => []
        ]);

        // Create test transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TEST-TRANS-123',
            'gateway_name' => 'SSLCommerz',
            'amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'order_id' => $order->id
        ]);

        $response = $this->postJson('/api/payment/success', [
            'tran_id' => 'TEST-TRANS-123',
            'status' => 'VALID',
            'val_id' => 'TEST-VAL-123'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'TEST-TRANS-123',
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function it_handles_payment_failure_callback()
    {
        // Create test order
        $order = Order::create([
            'order_number' => 'ORD-TEST-123',
            'user_id' => $this->user->id,
            'total_amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'billing_address' => [],
            'shipping_address' => []
        ]);

        // Create test transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TEST-TRANS-123',
            'gateway_name' => 'SSLCommerz',
            'amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'order_id' => $order->id
        ]);

        $response = $this->postJson('/api/payment/fail', [
            'tran_id' => 'TEST-TRANS-123',
            'status' => 'FAILED'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'TEST-TRANS-123',
            'status' => 'failed'
        ]);
    }

    /** @test */
    public function it_handles_payment_cancel_callback()
    {
        // Create test order
        $order = Order::create([
            'order_number' => 'ORD-TEST-123',
            'user_id' => $this->user->id,
            'total_amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'billing_address' => [],
            'shipping_address' => []
        ]);

        // Create test transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TEST-TRANS-123',
            'gateway_name' => 'SSLCommerz',
            'amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'order_id' => $order->id
        ]);

        $response = $this->postJson('/api/payment/cancel', [
            'tran_id' => 'TEST-TRANS-123'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'TEST-TRANS-123',
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function it_handles_payment_ipn_callback()
    {
        // Create test order
        $order = Order::create([
            'order_number' => 'ORD-TEST-123',
            'user_id' => $this->user->id,
            'total_amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'billing_address' => [],
            'shipping_address' => []
        ]);

        // Create test transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TEST-TRANS-123',
            'gateway_name' => 'SSLCommerz',
            'amount' => 200,
            'currency' => 'BDT',
            'status' => 'pending',
            'order_id' => $order->id
        ]);

        $response = $this->postJson('/api/payment/ipn', [
            'tran_id' => 'TEST-TRANS-123',
            'status' => 'VALID',
            'val_id' => 'TEST-VAL-123'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'TEST-TRANS-123',
            'status' => 'completed'
        ]);
    }
} 