<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

class InvoicePdfService
{
    // Shared by the admin download route and the customer-facing API route —
    // same rendered document either side, no duplicated layout logic.
    public function render(Invoice $invoice): PdfDocument
    {
        $invoice->loadMissing('items', 'customer', 'merchant');

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4');
    }
}
