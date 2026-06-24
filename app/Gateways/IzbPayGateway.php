<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Data\GatewayResult;
use Illuminate\Support\Str;

class IzbPayGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly bool   $verifySsl = true,
    ) {}

    public function driver(): string
    {
        return 'izb';
    }

    public function initiate(string $phone, float $amount, string $reference): GatewayResult
    {
        $mobile    = $this->normaliseMobile($phone);
        $amountStr = number_format($amount, 2, '.', '');
        $xml       = $this->buildSoapEnvelope($amountStr, $mobile, $reference);

        [$body, $curlError] = $this->post($xml);

        if ($curlError) {
            return new GatewayResult(false, $reference, 'failed', "Network error: {$curlError}");
        }

        return $this->parseResponse((string) $body, $reference);
    }

    public function verify(string $gatewayReference): GatewayResult
    {
        // IZB/CGRATE is synchronous — status is known at initiation time.
        return new GatewayResult(true, $gatewayReference, 'completed');
    }

    private function normaliseMobile(string $mobile): string
    {
        return substr(preg_replace('/\D/', '', $mobile), -10);
    }

    private function buildSoapEnvelope(string $amount, string $mobile, string $ref): string
    {
        $u = htmlspecialchars($this->username, ENT_XML1);
        $p = htmlspecialchars($this->password, ENT_XML1);

        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                              xmlns:kon="http://konik.cgrate.com">
                <soapenv:Header>
                    <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                                   soapenv:mustUnderstand="1">
                        <wsse:UsernameToken xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"
                                            wsu:Id="{$u}">
                            <wsse:Username>{$u}</wsse:Username>
                            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">{$p}</wsse:Password>
                        </wsse:UsernameToken>
                    </wsse:Security>
                </soapenv:Header>
                <soapenv:Body>
                    <kon:processCustomerPayment>
                        <transactionAmount>{$amount}</transactionAmount>
                        <customerMobile>{$mobile}</customerMobile>
                        <paymentReference>{$ref}</paymentReference>
                    </kon:processCustomerPayment>
                </soapenv:Body>
            </soapenv:Envelope>
            XML;
    }

    /** @return array{0: string|false, 1: string} */
    private function post(string $xml): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => rtrim($this->baseUrl, '/') . '/Konik/KonikWs',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/soap+xml'],
            CURLOPT_TIMEOUT        => 80,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
        ]);
        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        return [$response, $error];
    }

    private function parseResponse(string $body, string $fallbackRef): GatewayResult
    {
        if (trim($body) === '') {
            return new GatewayResult(false, $fallbackRef, 'failed', 'Empty response from IZB gateway.');
        }

        $clean = preg_replace('/(<\/?)[\w]+:/', '$1', $body);
        $xml   = @simplexml_load_string((string) $clean);

        if ($xml === false) {
            return new GatewayResult(false, $fallbackRef, 'failed', 'Malformed XML from IZB gateway.');
        }

        $node = $xml->Body->processCustomerPaymentResponse->return ?? null;
        if ($node === null) {
            return new GatewayResult(false, $fallbackRef, 'failed', 'Unexpected IZB response structure.');
        }

        $code    = (string) $node->responseCode;
        $msg     = (string) $node->responseMessage;
        $payId   = (string) $node->paymentID;
        $success = ($code === '0');

        return new GatewayResult(
            success:      $success,
            reference:    $success ? $payId : $fallbackRef,
            status:       $success ? 'completed' : 'failed',
            errorMessage: $success ? null : "{$msg} (code: {$code})",
        );
    }
}
