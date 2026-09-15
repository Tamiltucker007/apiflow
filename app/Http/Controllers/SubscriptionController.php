<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Http\Requests\ChangePlanRequest;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\SubscriptionService;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(Merchant $merchant): View
    {
        return view('subscriptions.index', ['merchant' => $merchant]);
    }

    public function data(Merchant $merchant): JsonResponse
    {
        $plans = $merchant->plans()->active()->orderBy('name')->get();

        $query = Subscription::query()
            ->where('subscriptions.merchant_id', $merchant->id)
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select('subscriptions.*', 'customers.name as customer_name', 'plans.name as plan_name');

        return DataTables::of($query)
            ->addColumn('status_badge', function (Subscription $s) {
                $classes = match ($s->status) {
                    SubscriptionStatus::Active => 'bg-green-100 text-green-700',
                    SubscriptionStatus::Cancelled => 'bg-red-100 text-red-700',
                    SubscriptionStatus::Expired => 'bg-gray-100 text-gray-500',
                };

                return '<span class="px-2 py-0.5 rounded text-xs font-medium '.$classes.'">'.ucfirst($s->status->value).'</span>';
            })
            ->addColumn('period', fn (Subscription $s) => $s->current_period_start->format('d M Y').' – '.$s->current_period_end->format('d M Y'))
            ->addColumn('actions', function (Subscription $s) use ($merchant, $plans) {
                if ($s->status !== SubscriptionStatus::Active) {
                    return '<span class="text-xs text-gray-400">—</span>';
                }

                $invoiceUrl = route('admin.subscriptions.generate-invoice', [$merchant, $s]);
                $changeUrl = route('admin.subscriptions.change-plan', [$merchant, $s]);
                $cancelUrl = route('admin.subscriptions.cancel', [$merchant, $s]);

                // Only same-billing-cycle plans are offered — switching cycle
                // length (e.g. monthly to quarterly) mid-cycle isn't supported
                // yet, since proration assumes the period's own cycle length.
                $options = $plans->where('billing_cycle', $s->plan->billing_cycle)
                    ->map(fn (Plan $plan) => '<option value="'.$plan->id.'"'.($plan->id === $s->plan_id ? ' selected' : '').'>'.e($plan->name).'</option>')->implode('');

                $changeConfirmTemplate = e("Change {$s->customer_name}'s plan to {value}? Usage before today stays billed at the old plan's rate, usage after at the new plan's rate.");
                $cancelConfirm = e("Cancel {$s->customer_name}'s subscription immediately? They will stop being metered right away — this cannot be undone.");

                return '<div class="flex items-center justify-end gap-3">'
                    .'<form method="POST" action="'.$invoiceUrl.'">'.csrf_field()
                    .'<button class="action-link">Generate Invoice</button></form>'
                    .'<form method="POST" action="'.$changeUrl.'" class="flex items-center gap-1"'
                        .' data-confirm-template="'.$changeConfirmTemplate.'"'
                        .' data-confirm-title="Change Plan" data-confirm-action="Change Plan">'.csrf_field().method_field('PUT')
                    .'<select name="plan_id" class="rounded border-gray-300 text-xs py-1 focus:border-indigo-500 focus:ring-indigo-500">'.$options.'</select>'
                    .'<button class="action-link action-edit">Change</button></form>'
                    .'<form method="POST" action="'.$cancelUrl.'"'
                        .' data-confirm="'.$cancelConfirm.'"'
                        .' data-confirm-title="Cancel Subscription" data-confirm-variant="danger" data-confirm-action="Cancel Subscription">'.csrf_field().method_field('DELETE')
                    .'<button class="action-link action-danger">Cancel</button></form>'
                    .'</div>';
            })
            ->filterColumn('customer_name', fn ($query, $keyword) => $query->where('customers.name', 'like', "%{$keyword}%"))
            ->filterColumn('plan_name', fn ($query, $keyword) => $query->where('plans.name', 'like', "%{$keyword}%"))
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function changePlan(ChangePlanRequest $request, Merchant $merchant, Subscription $subscription): RedirectResponse
    {
        $this->ensureBelongsToMerchant($subscription, $merchant);

        $newPlan = Plan::findOrFail($request->validated('plan_id'));
        $this->subscriptions->changePlan($subscription, $newPlan);

        return redirect()->route('admin.subscriptions.index', $merchant)
            ->with('status', "Plan changed to {$newPlan->name}, effective today.");
    }

    public function generateInvoice(Merchant $merchant, Subscription $subscription, BillingService $billing): RedirectResponse
    {
        $this->ensureBelongsToMerchant($subscription, $merchant);

        $invoice = $billing->generateInvoice($subscription);

        return redirect()->route('admin.invoices.show', [$merchant, $invoice])
            ->with('status', 'Invoice generated.');
    }

    public function cancel(Merchant $merchant, Subscription $subscription): RedirectResponse
    {
        $this->ensureBelongsToMerchant($subscription, $merchant);

        $this->subscriptions->cancel($subscription);

        return redirect()->route('admin.subscriptions.index', $merchant)
            ->with('status', 'Subscription cancelled.');
    }
}
