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

    public function index(): Response
    {
        $school = app('current_school');

        $saved = SchoolSetting::where('school_id', $school->id)
            ->pluck('value', 'key')
            ->all();

        $settings = collect(self::KEYS)->mapWithKeys(
            fn ($label, $key) => [$key => ['label' => $label, 'value' => $saved[$key] ?? '']]
        )->all();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
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
}
