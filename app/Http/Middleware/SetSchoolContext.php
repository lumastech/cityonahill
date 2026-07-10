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
        $user = $request->user();

        if (! $user) {
            return null;
        }

        // Super-admin can switch branches via query param or session,
        // defaulting to the first active branch so a fresh selection
        // is never required on single-branch installs.
        if ($user->hasRole('super-admin')) {
            $schoolId = $request->query('school_id') ?? $request->session()->get('school_id');

            if ($schoolId) {
                $school = School::find($schoolId);

                if ($school) {
                    $request->session()->put('school_id', $school->id);

                    return $school;
                }
            }

            return School::active()->orderBy('id')->first();
        }

        // Staff: use their assigned branch
        if (class_exists(Staff::class) && $user->staff?->school_id) {
            return School::find($user->staff->school_id);
        }

        // Direct branch assignment on user record
        if ($user->school_id) {
            return School::find($user->school_id);
        }

        // Parent: use first child's branch
        if ($user->is_parent && class_exists(Pupil::class)) {
            $pupil = Pupil::whereHas('guardians', fn ($q) => $q->where('user_id', $user->id))
                ->first();

            if ($pupil?->school_id) {
                return School::find($pupil->school_id);
            }
        }

        return null;
    }
}
