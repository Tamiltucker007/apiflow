<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Models\Merchant;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(private PlanService $plans) {}

    public function index(Merchant $merchant): View
    {
        return view('plans.index', [
            'merchant' => $merchant,
            'plans' => $merchant->plans()->latest()->get(),
        ]);
    }

    public function create(Merchant $merchant): View
    {
        return view('plans.create', ['merchant' => $merchant]);
    }

    public function store(StorePlanRequest $request, Merchant $merchant): RedirectResponse
    {
        $this->plans->create($merchant, $request->validated());

        return redirect()->route('merchants.plans.index', $merchant)
            ->with('status', 'Plan created.');
    }

    public function toggle(Merchant $merchant, Plan $plan): RedirectResponse
    {
        // {plan} binds before merchant.access middleware runs, so the tenant
        // scope can't protect this route — check ownership explicitly.
        abort_unless($plan->merchant_id === $merchant->id, 404);

        $this->plans->toggleActive($plan);

        return redirect()->route('merchants.plans.index', $merchant)
            ->with('status', $plan->is_active ? 'Plan activated.' : 'Plan deactivated.');
    }
}
