<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $school = app('current_school');

        $logs = AuditLog::where('school_id', $school->id)
            ->with('user:id,name')
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->input('model'), fn ($q, $m) => $q->where('auditable_type', 'like', "%{$m}%"))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs'    => $logs,
            'filters' => $request->only('action', 'model'),
        ]);
    }
}
