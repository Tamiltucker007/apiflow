<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Jobs\AggregateUsageJob;
use App\Models\Customer;
use App\Models\Merchant;
use App\Services\CustomerService;
use App\Services\UsageService;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private CustomerService $customers) {}

    public function index(Merchant $merchant): View
    {
        return view('customers.index', ['merchant' => $merchant]);
    }

    public function data(Merchant $merchant): JsonResponse
    {
        $canManage = auth()->user()->role !== UserRole::MerchantStaff;

        return DataTables::of(Customer::query()->where('merchant_id', $merchant->id))
            ->addColumn('name_link', function (Customer $customer) use ($merchant) {
                $url = route('merchants.customers.show', [$merchant, $customer]);

                return '<a href="'.$url.'" class="text-indigo-600 hover:underline">'.e($customer->name).'</a>';
            })
            ->addColumn('phone_fmt', fn (Customer $customer) => $customer->phone ?? '—')
            ->addColumn('registered', fn (Customer $customer) => $customer->created_at->format('d M Y'))
            ->addColumn('actions', function (Customer $customer) use ($merchant, $canManage) {
                if (! $canManage) {
                    return '';
                }

                $editUrl = route('merchants.customers.edit', [$merchant, $customer]);
                $destroyUrl = route('merchants.customers.destroy', [$merchant, $customer]);

                return '<div class="flex items-center justify-end gap-1">'
                    .'<a href="'.$editUrl.'" class="action-link action-edit">Edit</a>'
                    .'<form method="POST" action="'.$destroyUrl.'"'
                        .' data-confirm="'.e("Delete {$customer->name}? This cannot be undone.").'"'
                        .' data-confirm-title="Delete Customer" data-confirm-variant="danger" data-confirm-action="Delete">'.csrf_field().method_field('DELETE')
                    .'<button class="action-link action-danger">Delete</button></form>'
                    .'</div>';
            })
            ->filterColumn('name_link', fn ($query, $keyword) => $query->where('customers.name', 'like', "%{$keyword}%"))
            ->orderColumn('name_link', 'name $1')
            ->rawColumns(['name_link', 'actions'])
            ->make(true);
    }

    public function create(Merchant $merchant): View
    {
        return view('customers.create', ['merchant' => $merchant]);
    }

    public function store(StoreCustomerRequest $request, Merchant $merchant): RedirectResponse
    {
        $this->customers->create($merchant, $request->validated());

        return redirect()->route('merchants.customers.index', $merchant)
            ->with('status', 'Customer registered.');
    }

    public function show(Merchant $merchant, Customer $customer): View
    {
        $this->ensureBelongsToMerchant($customer, $merchant);

        return view('customers.show', [
            'merchant' => $merchant,
            'customer' => $customer,
            'apiKeys' => $customer->apiCredentials()->latest()->get(),
        ]);
    }

    public function edit(Merchant $merchant, Customer $customer): View
    {
        $this->ensureBelongsToMerchant($customer, $merchant);

        return view('customers.edit', ['merchant' => $merchant, 'customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Merchant $merchant, Customer $customer): RedirectResponse
    {
        $this->ensureBelongsToMerchant($customer, $merchant);

        $this->customers->update($customer, $request->validated());

        return redirect()->route('merchants.customers.index', $merchant)
            ->with('status', 'Customer updated.');
    }

    public function destroy(Merchant $merchant, Customer $customer): RedirectResponse
    {
        $this->ensureBelongsToMerchant($customer, $merchant);

        $this->customers->delete($customer);

        return redirect()->route('merchants.customers.index', $merchant)
            ->with('status', 'Customer deleted.');
    }

    /**
     * Demo/dev helper: backfills a week of realistic usage for this customer
     * and runs the aggregation job inline (not queued) so the dashboard
     * reflects it immediately — lets a walkthrough show the usage -> billing
     * flow without scripting real POST /usage calls. Disabled in production.
     */
    public function simulateUsage(Merchant $merchant, Customer $customer, UsageService $usage): RedirectResponse
    {
        abort_if(app()->isProduction(), 404);

        $this->ensureBelongsToMerchant($customer, $merchant);

        try {
            $result = $usage->simulateUsage($customer);
        } catch (ValidationException) {
            return redirect()->route('merchants.customers.show', [$merchant, $customer])
                ->with('error', 'This customer has no active subscription — subscribe them to a plan first.');
        }

        AggregateUsageJob::dispatchSync();

        return redirect()->route('merchants.customers.show', [$merchant, $customer])
            ->with('status', "Simulated {$result['total_units']} usage units across {$result['days']} day(s). Dashboard updated.");
    }

    /**
     * Issues a fresh portal password for this customer, shown once (same
     * flash-and-forget pattern as ApiCredentialController::store()). The
     * admin is expected to relay it to the customer themselves.
     */
    public function generatePortalPassword(Merchant $merchant, Customer $customer): RedirectResponse
    {
        $this->ensureBelongsToMerchant($customer, $merchant);

        $password = $this->customers->generatePortalPassword($customer);

        return redirect()->route('merchants.customers.show', [$merchant, $customer])
            ->with('newPortalPassword', $password);
    }
}
