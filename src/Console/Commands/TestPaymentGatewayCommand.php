<?php
namespace faysal0x1\PaymentGateway\Console\Commands;

use Illuminate\Console\Command;
use faysal0x1\PaymentGateway\PaymentGatewayManager;

class TestPaymentGatewayCommand extends Command
{
    protected $signature = 'payment:test {gateway}';
    protected $description = 'Test a payment gateway connection';

    public function handle(PaymentGatewayManager $manager)
    {
        $gatewayName = $this->argument('gateway');
        
        try {
            $gateway = $manager->gateway($gatewayName);
            $this->info("Successfully connected to {$gatewayName} gateway");
            $this->info("Gateway class: " . get_class($gateway));
        } catch (\Exception $e) {
            $this->error("Failed to connect to {$gatewayName}: " . $e->getMessage());
        }
    }
}