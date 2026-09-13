<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use VerifiesTenantOwnership;

    public function index(Merchant $merchant): View
    {
        return view('invoices.index', [
            'merchant' => $merchant,
            'invoices' => $merchant->invoices()->with('customer')->latest()->get(),
        ]);
    }

    public function show(Merchant $merchant, Invoice $invoice): View
    {
        $this->ensureBelongsToMerchant($invoice, $merchant);

        return view('invoices.show', [
            'merchant' => $merchant,
            'invoice' => $invoice->load('items', 'customer', 'subscription'),
        ]);
    }
}
