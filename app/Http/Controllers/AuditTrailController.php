<?php

namespace App\Http\Controllers;

use App\Models\ValidationBatch;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function index(ValidationBatch $batch): View
    {
        Gate::authorize('view', $batch);

        $auditLogs = $batch->auditLogs()
            ->with(['user', 'row'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('validation.audit-trail', compact('batch', 'auditLogs'));
    }
}
