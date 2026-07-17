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
        $isHistory = $request->boolean('history');
        $hasFilters = $request->filled('search') || $request->filled('user') || $request->filled('action') || $request->filled('date');

        $query = $this->baseQuery($request);

        // Nothing is ever deleted, but the default view is today-only so the
        // table doesn't grow unbounded on screen. Older entries stay fully
        // reachable via the date filter, search, or "View Full History".
        if (! $isHistory && ! $hasFilters) {
            $query->whereDate('created_at', today());
        }

        $logs = $query->paginate(50)->withQueryString();

        $users = User::orderBy('name')->get()->reject(fn ($u) => $u->outranks($request->user()));
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity-logs.index', compact('logs', 'users', 'actions', 'showAll', 'isHistory', 'hasFilters'));
    }

    /**
     * Render the currently filtered log as a print-friendly page, same rules
     * as index(). The page auto-opens the browser's print dialog, where the
     * user picks "Save as PDF" — no server-side PDF library required, matching
     * how the crop-cycle planner does it.
     */
    public function exportPdf(Request $request)
    {
        $isHistory = $request->boolean('history');
        $hasFilters = $request->filled('search') || $request->filled('user') || $request->filled('action') || $request->filled('date');

        $query = $this->baseQuery($request);

        if (! $isHistory && ! $hasFilters) {
            $query->whereDate('created_at', today());
        }

        $logs = $query->limit(2000)->get();

        return view('activity-logs.pdf', [
            'logs' => $logs,
            'generatedAt' => now(),
            'scopeLabel' => $isHistory || $hasFilters ? 'Filtered results' : 'Today, ' . today()->format('M d, Y'),
        ]);
    }

    /**
     * Shared filtering: auth noise, rank-based visibility, and the
     * search/user/action/date filters used by both the screen and the PDF.
     */
    private function baseQuery(Request $request)
    {
        $showAll = $request->boolean('all');
        $actor = $request->user();

        $query = ActivityLog::with('user')->latest();

        // Hide auth noise unless manager explicitly asks for it
        if (! $showAll) {
            $query->whereNotIn('action', self::AUTH_ACTIONS)
                  ->where(function ($q) {
                      $q->whereNull('subject_type')
                        ->orWhereNotIn('subject_type', self::SKIP_TYPES);
                  });
        }

        // Nobody sees activity performed by an account senior to their own.
        $seniorRoles = collect(User::ROLE_RANK)
            ->filter(fn ($rank) => $rank < $actor->rank())
            ->keys();

        if ($seniorRoles->isNotEmpty()) {
            $query->whereDoesntHave('user', fn ($q) => $q->whereIn('role', $seniorRoles));
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

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        return $query;
    }
}
