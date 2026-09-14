<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanSelectionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function create(): View|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        // Already subscribed (e.g. revisiting this URL directly) — nothing
        // to choose, send them to the dashboard that already shows it.
        if ($customer->activeSubscription()->exists()) {
            return redirect()->route('dashboard');
        }

        $plans = $customer->merchant->plans()->active()->orderBy('base_price_cents')->get();

        return view('portal.choose-plan', ['plans' => $plans]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $request->validate(['plan_id' => ['required', 'integer']]);

        // Scoped by the customer's own merchant_id, not a route param —
        // picking another merchant's plan id here 404s instead of leaking
        // whether that id exists.
        $plan = Plan::where('merchant_id', $customer->merchant_id)->findOrFail($data['plan_id']);

        try {
            $this->subscriptions->subscribe($customer, $plan);
        } catch (ValidationException) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard')->with('status', "You're subscribed to {$plan->name}.");
    }
}
