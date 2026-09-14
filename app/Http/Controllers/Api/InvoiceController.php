<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Lets a customer's own app list and download its invoices — customers have
// no dashboard login (see README), so this is their only access to invoice
// PDFs, authenticated the same way as every other API route: the API key.
class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('apiCustomer');

        $invoices = Invoice::where('customer_id', $customer->id)
            ->orderByDesc('period_start')
            ->get(['id', 'invoice_number', 'period_start', 'period_end', 'total_amount_cents', 'currency', 'status']);

        return response()->json(['data' => $invoices]);
    }

    public function download(Request $request, Invoice $invoice, InvoicePdfService $pdf): Response
    {
        $customer = $request->attributes->get('apiCustomer');

        // Invoice route-model binding resolves before auth.apikey sets tenant
        // context (same Laravel timing gotcha as the dashboard routes), so
        // ownership is checked explicitly rather than trusted from the scope.
        abort_unless($invoice->customer_id === $customer->id, 404);

        return $pdf->render($invoice)->download("{$invoice->invoice_number}.pdf");
    }
}
