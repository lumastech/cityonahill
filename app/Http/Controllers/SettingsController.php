<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const KEYS = [
        'term_fee'            => 'Term Fee (ZMW)',
        'currency'            => 'Currency',
        'sms_sender_id'       => 'SMS Sender ID',
        'school_motto'        => 'School Motto',
        'report_card_footer'  => 'Report Card Footer Note',
        'library_borrow_days' => 'Library Borrow Period (days)',
        'feeding_cost_per_day'=> 'Feeding Cost Per Day (ZMW)',
    ];

    private const PAYMENT_KEYS = [
        'payment.cash_enabled',
        'payment.izb_enabled',
        'payment.izb_base_url',
        'payment.izb_username',
        'payment.izb_password',
        'payment.izb_verify_ssl',
        'payment.lenco_enabled',
        'payment.lenco_base_url',
        'payment.lenco_api_token',
        'payment.lenco_webhook_hash',
    ];

    public function index(): Response
    {
        $school = app('current_school');

        $saved = SchoolSetting::where('school_id', $school->id)
            ->pluck('value', 'key')
            ->all();

        $settings = collect(self::KEYS)->mapWithKeys(
            fn ($label, $key) => [$key => ['label' => $label, 'value' => $saved[$key] ?? '']]
        )->all();

        $paymentSettings = [
            'cash_enabled'       => $saved['payment.cash_enabled']       ?? '1',
            'izb_enabled'        => $saved['payment.izb_enabled']        ?? '0',
            'izb_base_url'       => $saved['payment.izb_base_url']       ?? 'https://543.cgrate.co.zm:8443',
            'izb_username'       => $saved['payment.izb_username']       ?? '',
            'izb_password'       => $saved['payment.izb_password']       ?? '',
            'izb_verify_ssl'     => $saved['payment.izb_verify_ssl']     ?? '1',
            'lenco_enabled'      => $saved['payment.lenco_enabled']      ?? '0',
            'lenco_base_url'     => $saved['payment.lenco_base_url']     ?? 'https://api.lenco.co/access/v2',
            'lenco_api_token'    => $saved['payment.lenco_api_token']    ?? '',
            'lenco_webhook_hash' => $saved['payment.lenco_webhook_hash'] ?? '',
        ];

        return Inertia::render('Settings/Index', [
            'settings'         => $settings,
            'payment_settings' => $paymentSettings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = app('current_school');

        $data = $request->validate([
            'settings'   => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            if (! array_key_exists($key, self::KEYS)) {
                continue;
            }
            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => $key],
                ['value' => $value ?? '']
            );
        }

        return back()->with('success', 'Settings saved.');
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $school = app('current_school');

        $data = $request->validate([
            'cash_enabled'       => ['boolean'],
            'izb_enabled'        => ['boolean'],
            'izb_base_url'       => ['nullable', 'string', 'max:200'],
            'izb_username'       => ['nullable', 'string', 'max:100'],
            'izb_password'       => ['nullable', 'string', 'max:200'],
            'izb_verify_ssl'     => ['boolean'],
            'lenco_enabled'      => ['boolean'],
            'lenco_base_url'     => ['nullable', 'string', 'max:200'],
            'lenco_api_token'    => ['nullable', 'string', 'max:300'],
            'lenco_webhook_hash' => ['nullable', 'string', 'max:300'],
        ]);

        foreach ($data as $key => $value) {
            $settingKey = "payment.{$key}";
            if (! in_array($settingKey, self::PAYMENT_KEYS)) {
                continue;
            }
            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => $settingKey],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : ($value ?? '')]
            );
        }

        return back()->with('success', 'Payment settings saved.');
    }
}
