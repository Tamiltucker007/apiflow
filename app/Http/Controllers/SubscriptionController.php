<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePlanRequest;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\SubscriptionService;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(Merchant $merchant): View
    {
        return view('subscriptions.index', [
            'merchant' => $merchant,
            'subscriptions' => $merchant->subscriptions()->with(['customer', 'plan'])->latest()->get(),
            'customers' => $merchant->customers()->orderBy('name')->get(),
            'plans' => $merchant->plans()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(CreateSubscriptionRequest $request, Merchant $merchant): RedirectResponse
    {
        $customer = Customer::findOrFail($request->validated('customer_id'));
        $plan = Plan::findOrFail($request->validated('plan_id'));
        $this->ensureBelongsToMerchant($customer, $merchant);
        $this->ensureBelongsToMerchant($plan, $merchant);

        $this->subscriptions->subscribe($customer, $plan);

        return redirect()->route('merchants.subscriptions.index', $merchant)
            ->with('status', 'Subscription created.');
    }

    public function changePlan(ChangePlanRequest $request, Merchant $merchant, Subscription $subscription): RedirectResponse
    {
        $this->ensureBelongsToMerchant($subscription, $merchant);

        $newPlan = Plan::findOrFail($request->validated('plan_id'));
        $this->subscriptions->changePlan($subscription, $newPlan);

        return redirect()->route('merchants.subscriptions.index', $merchant)
            ->with('status', "Plan changed to {$newPlan->name}, effective today.");
    }

    public function generateInvoice(Merchant $merchant, Subscription $subscription, BillingService $billing): RedirectResponse
    {
        $this->ensureBelongsToMerchant($subscription, $merchant);

        $invoice = $billing->generateInvoice($subscription);

        return redirect()->route('merchants.invoices.show', [$merchant, $invoice])
            ->with('status', 'Invoice generated.');
    }
}
