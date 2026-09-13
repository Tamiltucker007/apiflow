<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Services\CustomerService;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private CustomerService $customers) {}

    public function index(Merchant $merchant): View
    {
        return view('customers.index', [
            'merchant' => $merchant,
            'customers' => $merchant->customers()->latest()->get(),
        ]);
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
}
