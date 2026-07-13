<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Audit-trail viewer: business activity overview for managers.
 * Auth events (sign-in, registration) are hidden by default.
 */
class ActivityLogController extends Controller
{
    /** Actions that are auth/session noise, not business work. */
    private const AUTH_ACTIONS = ['signed_in', 'registered'];

    /** Model types that are infrastructure, not field operations. */
    private const SKIP_TYPES = [User::class, \App\Models\ApprovedEmail::class];

    public function index(Request $request)
    {
        $showAll = $request->boolean('all');

        $query = ActivityLog::with('user')->latest();

        // Hide auth noise unless manager explicitly asks for it
        if (! $showAll) {
            $query->whereNotIn('action', self::AUTH_ACTIONS)
                  ->where(function ($q) {
                      $q->whereNull('subject_type')
                        ->orWhereNotIn('subject_type', self::SKIP_TYPES);
                  });
        }

        if ($userId = $request->input('user')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $logs = $query->paginate(50)->withQueryString();

        $users = User::orderBy('name')->get();
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity-logs.index', compact('logs', 'users', 'actions', 'showAll'));
    }
}
