<?php
// src/Contracts/PaymentVerificationInterface.php
namespace faysal0x1\PaymentGateway\Contracts;

interface PaymentVerificationInterface {
    public function verify(array $data);
}