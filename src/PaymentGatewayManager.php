<?php
namespace faysal0x1\PaymentGateway;

use faysal0x1\PaymentGateway\Contracts\PaymentGatewayInterface;
use faysal0x1\PaymentGateway\Drivers\AbstractDriver;
use faysal0x1\PaymentGateway\Exceptions\InvalidGatewayException;
use faysal0x1\PaymentGateway\Models\PaymentGateway as PaymentGatewayModel;

class PaymentGatewayManager
{
    protected $app;
    protected $gateways = [];
    protected $config;

    public function __construct($app)
    {
        $this->app = $app;
        $this->config = $app['config']['payment-gateway'];
    }

    public function gateway(string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: $this->getDefaultGateway();

        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->resolve($name);
        }

        return $this->gateways[$name];
    }

    protected function resolve(string $name): PaymentGatewayInterface
    {
        $config = $this->config['gateways'][$name] ?? [];
        
        if (empty($config)) {
            throw new InvalidGatewayException("Gateway [{$name}] is not configured.");
        }

        $driverClass = $config['driver'];
        
        if (!class_exists($driverClass)) {
            throw new InvalidGatewayException("Driver [{$driverClass}] for gateway [{$name}] not found.");
        }

        // Try to get from DB first
        $gatewayModel = PaymentGatewayModel::getGateway($name);
        
        return new $driverClass($config, $gatewayModel);
    }

    public function getDefaultGateway(): string
    {
        return $this->config['default'];
    }

    public function __call($method, $parameters)
    {
        return $this->gateway()->$method(...$parameters);
    }
}