<?php

namespace App\Http\Controllers;

use App\Models\BudgetTracking;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Resolve the BudgetTracking bound by RequireBudgetTracking middleware.
     * Controllers call $this->budget($request) instead of auth()->user() for scoping.
     */
    protected function budget(Request $request): BudgetTracking
    {
        return $request->attributes->get('budgetTracking');
    }
}
