<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StorePlanRequest;
use App\Models\Merchant;
use App\Models\Plan;
use App\Services\PlanService;
use App\Support\Money;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PlanController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private PlanService $plans) {}

    public function index(Merchant $merchant): View
    {
        return view('plans.index', ['merchant' => $merchant]);
    }

    public function data(Merchant $merchant): JsonResponse
    {
        $canManage = auth()->user()->role !== UserRole::MerchantStaff;

        return DataTables::of(Plan::query()->where('merchant_id', $merchant->id))
            ->addColumn('billing_cycle_label', fn (Plan $plan) => ucfirst($plan->billing_cycle->value))
            ->addColumn('base_price', fn (Plan $plan) => Money::format($plan->base_price_cents, $plan->currency))
            ->addColumn('included_units_fmt', fn (Plan $plan) => number_format($plan->included_units))
            ->addColumn('overage_rate', fn (Plan $plan) => Money::format($plan->overage_rate_cents, $plan->currency).' / unit')
            ->addColumn('status_badge', function (Plan $plan) {
                $classes = $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';

                return '<span class="px-2 py-0.5 rounded text-xs '.$classes.'">'.($plan->is_active ? 'Active' : 'Inactive').'</span>';
            })
            ->addColumn('actions', function (Plan $plan) use ($merchant, $canManage) {
                if (! $canManage) {
                    return '';
                }

                $editUrl = route('merchants.plans.edit', [$merchant, $plan]);
                $toggleUrl = route('merchants.plans.toggle', [$merchant, $plan]);
                $destroyUrl = route('merchants.plans.destroy', [$merchant, $plan]);
                $toggleLabel = $plan->is_active ? 'Deactivate' : 'Activate';

                return '<div class="flex items-center justify-end gap-1">'
                    .'<a href="'.$editUrl.'" class="action-link action-edit">Edit</a>'
                    .'<form method="POST" action="'.$toggleUrl.'">'.csrf_field().method_field('PUT')
                    .'<button class="action-link">'.$toggleLabel.'</button></form>'
                    .'<form method="POST" action="'.$destroyUrl.'" onsubmit="return confirm(\'Delete this plan? This cannot be undone.\')">'.csrf_field().method_field('DELETE')
                    .'<button class="action-link action-danger">Delete</button></form>'
                    .'</div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
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
        $this->ensureBelongsToMerchant($plan, $merchant);

        $this->plans->toggleActive($plan);

        return redirect()->route('merchants.plans.index', $merchant)
            ->with('status', $plan->is_active ? 'Plan activated.' : 'Plan deactivated.');
    }

    public function edit(Merchant $merchant, Plan $plan): View
    {
        $this->ensureBelongsToMerchant($plan, $merchant);

        return view('plans.edit', ['merchant' => $merchant, 'plan' => $plan]);
    }

    public function update(StorePlanRequest $request, Merchant $merchant, Plan $plan): RedirectResponse
    {
        $this->ensureBelongsToMerchant($plan, $merchant);

        $this->plans->update($plan, $request->validated());

        return redirect()->route('merchants.plans.index', $merchant)
            ->with('status', 'Plan updated.');
    }

    public function destroy(Merchant $merchant, Plan $plan): RedirectResponse
    {
        $this->ensureBelongsToMerchant($plan, $merchant);

        $this->plans->delete($plan);

        return redirect()->route('merchants.plans.index', $merchant)
            ->with('status', 'Plan deleted.');
    }
}
