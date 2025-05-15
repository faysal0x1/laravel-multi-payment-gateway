<?php
// src/Drivers/AbstractDriver.php
namespace faysal0x1\PaymentGateway\Drivers;

use faysal0x1\PaymentGateway\Contracts\PaymentGatewayInterface;
use faysal0x1\PaymentGateway\Contracts\PaymentVerificationInterface;
use faysal0x1\PaymentGateway\Exceptions\PaymentException;
use faysal0x1\PaymentGateway\Models\PaymentGateway;

abstract class AbstractDriver implements PaymentGatewayInterface, PaymentVerificationInterface
{
    protected $config;
    protected $credentials;
    protected $gatewayModel;

    public function __construct(array $config, PaymentGateway $gatewayModel = null)
    {
        $this->config = $config;
        $this->gatewayModel = $gatewayModel;
        $this->setCredentials();
    }

    abstract protected function setCredentials();

    protected function getCredential(string $key)
    {
        // First try to get from DB, then from config
        if ($this->gatewayModel && $value = $this->gatewayModel->credentials[$key] ?? null) {
            return $value;
        }

        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        throw new PaymentException("Credential {$key} not found for " . $this->getGatewayName());
    }
}