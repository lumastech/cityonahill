<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Term;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth.user' => fn () => $request->user()?->load(['roles', 'permissions', 'school']),

            'flash' => fn () => [
                'success'  => $request->session()->get('success'),
                'error'    => $request->session()->get('error'),
                'info'     => $request->session()->get('info'),
                'link_url' => $request->session()->get('link_url'),
            ],

            'current_school' => fn () => app()->bound('current_school')
                ? app('current_school')
                : null,

            'school_options' => fn () => $request->user()?->hasRole('super-admin')
                ? School::active()->orderBy('name')->get(['id', 'name'])
                : [],

            'terms' => fn () => $this->currentTerms(),

            'settings' => fn () => $this->mergedSettings(),

            'nav' => fn () => $request->user()
                ? app(MenuService::class)->forUser($request->user())
                : [],

            'auth.staff_profile_url' => fn () => $this->staffProfileUrl($request),
        ];
    }

    /** @return array<mixed> */
    private function currentTerms(): array
    {
        $school = app()->bound('current_school') ? app('current_school') : null;

        if (! $school || ! class_exists(Term::class)) {
            return [];
        }

        return Term::where('school_id', $school->id)
            ->where('status', 'active')
            ->get()
            ->toArray();
    }

    private function staffProfileUrl(Request $request): ?string
    {
        $user   = $request->user();
        $school = app()->bound('current_school') ? app('current_school') : null;

        if (! $user || ! $school) {
            return null;
        }

        $staff = Staff::where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->value('id');

        return $staff ? route('staff.show', $staff) : null;
    }

    /** @return array<string, mixed> */
    private function mergedSettings(): array
    {
        $school = app()->bound('current_school') ? app('current_school') : null;

        $global = Setting::all()->pluck('value', 'id')->toArray();

        if (! $school) {
            return $global;
        }

        $schoolSettings = SchoolSetting::where('school_id', $school->id)
            ->pluck('value', 'key')
            ->toArray();

        return array_merge($global, $schoolSettings);
    }
}
