<?php
// src/Contracts/PaymentGatewayInterface.php
namespace faysal0x1\PaymentGateway\Contracts;

interface PaymentGatewayInterface {
    public function pay(array $data);
    public function validate(array $data);
    public function ipn(array $data);
    public function refund(array $data);
    public function getGatewayName();
}