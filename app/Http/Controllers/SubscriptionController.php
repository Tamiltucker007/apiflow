<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
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

        $this->subscriptions->subscribe($customer, $plan);

        return redirect()->route('merchants.subscriptions.index', $merchant)
            ->with('status', 'Subscription created.');
    }
}
