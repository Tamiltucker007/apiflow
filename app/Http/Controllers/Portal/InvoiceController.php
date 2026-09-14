<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    // No merchant/tenant route param here — a portal request is scoped to
    // exactly the logged-in customer, and every lookup below is filtered by
    // that customer's own id, not route-model-bound. A customer requesting
    // someone else's invoice id gets a 404, not a 403, so id enumeration
    // can't distinguish "not yours" from "doesn't exist".
    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);

        return view('portal.invoice', ['invoice' => $invoice->load('items')]);
    }

    public function download(Invoice $invoice, InvoicePdfService $pdf): Response
    {
        $this->authorizeInvoice($invoice);

        return $pdf->render($invoice)->download("{$invoice->invoice_number}.pdf");
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless($invoice->customer_id === Auth::guard('customer')->id(), 404);
    }
}
