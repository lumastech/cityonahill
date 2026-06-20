<?php

namespace App\Http\Middleware;

use App\Models\Pupil;
use App\Models\School;
use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSchoolContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = $this->resolveSchool($request);

        // instance() stores null but PHP's isset() returns false for null values,
        // so bind() with a closure is used to correctly handle the null case.
        app()->bind('current_school', fn () => $school);

        return $next($request);
    }

    private function resolveSchool(Request $request): ?School
    {
        // Single-school mode
        if (! config('skuu.multi_school')) {
            $defaultId = config('skuu.default_school');

            return $defaultId ? School::find($defaultId) : null;
        }

        // Subdomain-based resolution — primary resolver in multi-school mode
        $school = $this->resolveBySubdomain($request);
        if ($school) {
            return $school;
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        // Super-admin can switch schools via query param or session
        if ($user->hasRole('super-admin')) {
            $schoolId = $request->query('school_id') ?? $request->session()->get('school_id');

            if ($schoolId) {
                $school = School::find($schoolId);

                if ($school) {
                    $request->session()->put('school_id', $school->id);

                    return $school;
                }
            }

            return null;
        }

        // Staff: use their assigned school
        if (class_exists(Staff::class) && $user->staff?->school_id) {
            return School::find($user->staff->school_id);
        }

        // Direct school assignment on user record
        if ($user->school_id) {
            return School::find($user->school_id);
        }

        // Parent: use first child's school
        if ($user->is_parent && class_exists(Pupil::class)) {
            $pupil = Pupil::whereHas('guardians', fn ($q) => $q->where('user_id', $user->id))
                ->first();

            if ($pupil?->school_id) {
                return School::find($pupil->school_id);
            }
        }

        return null;
    }

    private function resolveBySubdomain(Request $request): ?School
    {
        $host     = $request->getHost();
        $appHost  = parse_url(config('app.url'), PHP_URL_HOST) ?? '';

        // Strip the root domain to get the subdomain part
        if (! str_ends_with($host, '.' . $appHost)) {
            return null;
        }

        $subdomain = rtrim(substr($host, 0, -strlen('.' . $appHost)), '.');

        // Reserved subdomains that are never school portals
        if (in_array($subdomain, ['www', 'admin', 'api', 'mail', 'ftp', ''])) {
            return null;
        }

        return School::where('subdomain', $subdomain)->where('status', 'active')->first();
    }
}
