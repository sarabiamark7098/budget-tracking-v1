<?php

namespace App\Http\Middleware;

use App\Models\BudgetTracking;
use App\Models\BudgetTrackingMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's BudgetTracking from their membership
 * and binds it to the request as `budget_tracking`.
 *
 * If the user has no tracker, returns 403 so the frontend can gate them
 * back to the Budget Tracker setup page.
 */
class RequireBudgetTracking
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $member = BudgetTrackingMember::where('user_id', $user->id)->first();

        if (! $member) {
            return response()->json([
                'message' => 'You must create or join a Budget Tracker before using this feature.',
            ], 403);
        }

        $budget = BudgetTracking::with(['owner', 'members.user', 'allocations'])
            ->find($member->budget_tracking_id);

        if (! $budget) {
            return response()->json([
                'message' => 'Budget Tracker not found.',
            ], 404);
        }

        // Bind to request so controllers can access it via $request->budgetTracking
        $request->attributes->set('budgetTracking', $budget);

        return $next($request);
    }
}
