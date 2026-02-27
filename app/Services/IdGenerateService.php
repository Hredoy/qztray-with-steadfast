<?php

namespace App\Services;

use App\Models\Invoice;

class IdGenerateService
{
    public function generateNextSaleInvoiceNo(): string
    {
        $saleInvoiceCount = Invoice::where('created_at', 'like', date('Y-m-d').'%')->count();

        return 'SI-'.date('ymd').sprintf('%04d', $saleInvoiceCount + 1);
    }
}
