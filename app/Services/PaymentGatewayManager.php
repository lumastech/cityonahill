<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Gateways\IzbPayGateway;
use App\Gateways\LencoGateway;
use App\Models\SchoolSetting;

class PaymentGatewayManager
{
    /** Resolve the active gateway for a school. Returns null when only cash is enabled. */
    public function active(int $schoolId): ?PaymentGatewayInterface
    {
        if (SchoolSetting::get($schoolId, 'payment.izb_enabled', '0') === '1') {
            return new IzbPayGateway(
                baseUrl:   SchoolSetting::get($schoolId, 'payment.izb_base_url', 'https://543.cgrate.co.zm:8443'),
                username:  SchoolSetting::get($schoolId, 'payment.izb_username', ''),
                password:  SchoolSetting::get($schoolId, 'payment.izb_password', ''),
                verifySsl: SchoolSetting::get($schoolId, 'payment.izb_verify_ssl', '1') === '1',
            );
        }

        if (SchoolSetting::get($schoolId, 'payment.lenco_enabled', '0') === '1') {
            return new LencoGateway(
                baseUrl:     SchoolSetting::get($schoolId, 'payment.lenco_base_url', 'https://api.lenco.co/access/v2'),
                apiToken:    SchoolSetting::get($schoolId, 'payment.lenco_api_token', ''),
                webhookHash: SchoolSetting::get($schoolId, 'payment.lenco_webhook_hash', ''),
            );
        }

        return null;
    }

    public function cashEnabled(int $schoolId): bool
    {
        return SchoolSetting::get($schoolId, 'payment.cash_enabled', '1') === '1';
    }

    /** True when at least one gateway (or cash) is usable. */
    public function anyEnabled(int $schoolId): bool
    {
        return $this->cashEnabled($schoolId) || $this->active($schoolId) !== null;
    }

    /** Resolve a LencoGateway by school for webhook signature verification. */
    public function lencoForSchool(int $schoolId): ?LencoGateway
    {
        if (SchoolSetting::get($schoolId, 'payment.lenco_enabled', '0') !== '1') {
            return null;
        }

        return new LencoGateway(
            baseUrl:     SchoolSetting::get($schoolId, 'payment.lenco_base_url', ''),
            apiToken:    SchoolSetting::get($schoolId, 'payment.lenco_api_token', ''),
            webhookHash: SchoolSetting::get($schoolId, 'payment.lenco_webhook_hash', ''),
        );
    }
}
