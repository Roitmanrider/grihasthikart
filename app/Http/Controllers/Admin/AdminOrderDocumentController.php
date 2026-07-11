<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Order\Services\OrderDocumentService;
use App\Http\Controllers\Controller;
use App\Models\Order;

class AdminOrderDocumentController extends Controller
{
    public function __construct(
        private readonly OrderDocumentService $documents
    ) {}

    public function invoice(Order $order)
    {
        return view('documents.orders.invoice', $this->documents->invoiceData($order));
    }

    public function pickingSlip(Order $order)
    {
        return view('documents.orders.picking-slip', $this->documents->pickingSlipData($order));
    }

    public function packingSlip(Order $order)
    {
        return view('documents.orders.packing-slip', $this->documents->packingSlipData($order));
    }
}
