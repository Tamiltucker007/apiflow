<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Services\InvoicePdfService;
use App\Support\Money;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    use VerifiesTenantOwnership;

    public function index(Merchant $merchant): View
    {
        return view('invoices.index', ['merchant' => $merchant]);
    }

    public function data(Merchant $merchant): JsonResponse
    {
        $query = Invoice::query()
            ->where('invoices.merchant_id', $merchant->id)
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->select('invoices.*', 'customers.name as customer_name');

        return DataTables::of($query)
            ->addColumn('invoice_link', function (Invoice $invoice) use ($merchant) {
                $url = route('merchants.invoices.show', [$merchant, $invoice]);

                return '<a href="'.$url.'" class="text-indigo-600 hover:underline">'.e($invoice->invoice_number).'</a>';
            })
            ->addColumn('period', fn (Invoice $invoice) => $invoice->period_start->format('d M').' – '.$invoice->period_end->format('d M Y'))
            ->addColumn('total', fn (Invoice $invoice) => Money::format($invoice->total_amount_cents, $invoice->currency))
            ->addColumn('status_badge', fn (Invoice $invoice) => '<span class="px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700">'.$invoice->status->value.'</span>')
            ->addColumn('actions', function (Invoice $invoice) use ($merchant) {
                $url = route('merchants.invoices.download', [$merchant, $invoice]);

                return '<a href="'.$url.'" class="action-link action-edit">Download</a>';
            })
            ->filterColumn('invoice_link', fn ($query, $keyword) => $query->where('invoices.invoice_number', 'like', "%{$keyword}%"))
            ->orderColumn('invoice_link', 'invoice_number $1')
            ->filterColumn('customer_name', fn ($query, $keyword) => $query->where('customers.name', 'like', "%{$keyword}%"))
            ->rawColumns(['invoice_link', 'status_badge', 'actions'])
            ->make(true);
    }

    public function show(Merchant $merchant, Invoice $invoice): View
    {
        $this->ensureBelongsToMerchant($invoice, $merchant);

        return view('invoices.show', [
            'merchant' => $merchant,
            'invoice' => $invoice->load('items', 'customer', 'subscription'),
        ]);
    }

    public function download(Merchant $merchant, Invoice $invoice, InvoicePdfService $pdf): Response
    {
        $this->ensureBelongsToMerchant($invoice, $merchant);

        return $pdf->render($invoice)->download("{$invoice->invoice_number}.pdf");
    }
}
