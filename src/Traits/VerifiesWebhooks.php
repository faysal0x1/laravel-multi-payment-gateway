<?php
namespace faysal0x1\PaymentGateway\Traits;

use Illuminate\Http\Request;
use faysal0x1\PaymentGateway\Exceptions\PaymentException;

trait VerifiesWebhooks
{
    protected function verifyWebhookSignature(Request $request, string $secret)
    {
        $signature = $request->header('X-Signature') ?? $request->input('signature');
        
        if (!$signature) {
            throw new PaymentException('Missing webhook signature');
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($signature, $computedSignature)) {
            throw new PaymentException('Invalid webhook signature');
        }

        return true;
    }
}