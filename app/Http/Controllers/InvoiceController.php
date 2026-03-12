<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Services\IdGenerateService;
use App\Services\WhatsAppFreeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Picqer\Barcode\BarcodeGeneratorPNG;
use SabitAhmad\SteadFast\DTO\OrderRequest;
use SabitAhmad\SteadFast\SteadFast;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $query = Invoice::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('invoice_id', 'like', "%{$q}%")
                        ->orWhere('stead_fast_id', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            });

        $invoices = (clone $query)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalInvoiceSum = Invoice::sum('total');

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'q' => $q,
            ],
            'totalInvoiceSum' => $totalInvoiceSum,
        ]);
    }

    public function create()
    {
        return Inertia::render('invoices/Create');
    }

    public function store(Request $request, IdGenerateService $idGenerateService, SteadFast $steadfast)
    {
        $data = $this->validated($request);
        $data['invoice_id'] = $idGenerateService->generateNextSaleInvoiceNo();

        if ((int) $request->delivery_type === 1) {
            $request->merge(['cod' => 0]);
        }
        $invoice = Invoice::create($data);

        if ($invoice['delivery_type'] === 2) {
            $order = new OrderRequest(
                invoice: $invoice['invoice_id'],
                recipient_name: $invoice['name'],
                recipient_phone: $invoice['phone'],
                recipient_address: $invoice['address'],
                cod_amount: $invoice['cod'],
                note: $invoice['notes'] ?? 'Handle with care'
            );

            $response = $steadfast->createOrder($order);
            if ($response->status) {
                $invoice->stead_fast_id = $response->consignment['consignment_id'];
                $invoice->save();
            }
        }

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice, WhatsAppFreeService $whatsAppFreeService)
    {
        $whatsappSettings = BusinessSetting::where('key', 'whatsapp')->first();

        if ($whatsappSettings && $whatsappSettings->value) {
            $waData = $whatsappSettings->value;
            $waLink = $whatsAppFreeService->deliveryLink(deliveryPhone: $waData['delivery_man_phone'], message: $waData['delivery_man_message']);
        }

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'walink' => $waLink ?? null,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        return Inertia::render('invoices/Edit', [
            'invoice' => $invoice,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $this->validated($request);

        if ((int) $request->delivery_type === 1) {
            $request->merge(['cod' => 0]);
        }

        $invoice->update($data);

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function printInstruction(Invoice $invoice)
    {
        return view('prints.invoices.instruction', compact('invoice'));
    }

    public function printLogo(Invoice $invoice)
    {
        return view('prints.invoices.logo', compact('invoice'));
    }

    public function logoPdf(Invoice $invoice)
    {
        // IMPORTANT: DomPDF + SVG can be problematic on printing.
        // Best: use PNG/JPG.
        $pdf = Pdf::loadView('prints.pdf.logo-labels', [
            'invoice' => $invoice,
            'img1' => public_path('logo/thankyou.svg'),
            'img2' => public_path('logo/combine-logo.svg'),
        ])
            ->setPaper([0, 0, 144, 215.28]); // 2.00in x 2.99in (1in=72pt) => 144 x 215.28

        // stream = open in browser (perfect for iframe)
        return $pdf->stream("logos-{$invoice->id}.pdf");
    }

    public function packagingSlipPdf(Invoice $invoice)
    {
        // QR
        $qrText = $invoice->stead_fast_id ?: $invoice->invoice_id;

        $qrResult = (new Builder(
            writer: new PngWriter,
            writerOptions: [],
            validateResult: false,
            data: $qrText,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();

        $qrBase64 = 'data:image/png;base64,'.base64_encode($qrResult->getString());

        // Barcode
        $barcodeText = $invoice->stead_fast_id ?: $invoice->invoice_id;
        $generator = new BarcodeGeneratorPNG;

        $barcodeBase64 = 'data:image/png;base64,'.base64_encode(
            $generator->getBarcode($barcodeText, $generator::TYPE_CODE_39, 2, 80)
        );

        // Logos (base64)
        $brandLogo = $this->imgToDataUri(public_path('logo/SVG/Asset 5.svg'));
        $miniLogo = $this->imgToDataUri(public_path('logo/SVG/Asset 2.svg'));
        $packLogo = $this->imgToDataUri(public_path('logo/SVG/Asset 3.svg'));
        $courierLogo = $this->imgToDataUri(public_path('logo/SVG/Asset 4.svg'));

        // 3x4 inch paper
        $paper = [0, 0, 216, 288];

        $pdf = Pdf::loadView('prints.invoices.packaging-slip-pdf', [
            'invoice' => $invoice,
            'qrBase64' => $qrBase64,
            'barcodeBase64' => $barcodeBase64,
            'barcodeText' => $barcodeText,

            'brandLogo' => $brandLogo,
            'miniLogo' => $miniLogo,
            'packLogo' => $packLogo,
            'courierLogo' => $courierLogo,
        ])->setPaper($paper)->setOptions([
            'dpi' => 203,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf->stream("packaging-slip-{$invoice->id}.pdf");
    }

    public function packagingSlipPdfBulk(array $invoiceIds)
    {
        $invoices = Invoice::whereIn('id', $invoiceIds)->get();

        $slips = $invoices->map(function (Invoice $invoice) {
            // QR
            $qrText = $invoice->stead_fast_id ?: $invoice->invoice_id;

            $qrResult = (new Builder(
                writer: new PngWriter,
                writerOptions: [],
                validateResult: false,
                data: $qrText,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 220,
                margin: 0,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            ))->build();

            $qrBase64 = 'data:image/png;base64,'.base64_encode($qrResult->getString());

            // Barcode
            $barcodeText = $qrText;
            $generator = new BarcodeGeneratorPNG;

            $barcodeBase64 = 'data:image/png;base64,'.base64_encode(
                $generator->getBarcode($barcodeText, $generator::TYPE_CODE_39, 2, 80)
            );

            return [
                'invoice' => $invoice,
                'qrBase64' => $qrBase64,
                'barcodeBase64' => $barcodeBase64,
                'barcodeText' => $barcodeText,
            ];
        })->values();

        // Logos (only once)
        $logos = [
            'brandLogo' => $this->imgToDataUri(public_path('logo/SVG/Asset 5.svg')),
            'miniLogo' => $this->imgToDataUri(public_path('logo/SVG/Asset 2.svg')),
            'packLogo' => $this->imgToDataUri(public_path('logo/SVG/Asset 3.svg')),
            'courierLogo' => $this->imgToDataUri(public_path('logo/SVG/Asset 4.svg')),
        ];

        // 3x4 inch paper (216x288 pt)
        $paper = [0, 0, 216, 288];

        $pdf = Pdf::loadView('prints.invoices.packaging-slip-pdf-bulk', [
            'slips' => $slips,
            ...$logos,
        ])->setPaper($paper)->setOptions([
            'dpi' => 203,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf->stream('packaging-slips.pdf');
    }

    private function imgToDataUri(string $path): string
    {
        if (! file_exists($path)) {
            // helpful debug in logs
            logger()->error('Image not found: '.$path);

            return '';
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // SVG
        if ($ext === 'svg') {
            $svg = file_get_contents($path);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        }

        // PNG/JPG
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return "data:$mime;base64,".base64_encode(file_get_contents($path));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'wgt' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'delivery_type' => ['required', 'in:1,2'],
            'cod' => [
                'nullable',
                'numeric',
                'min:0',
                // required only if delivery_type == 2
                function ($attr, $value, $fail) use ($request) {
                    if ((int) $request->delivery_type === 2 && ($value === null || $value === '')) {
                        $fail('COD is required for Delivery.');
                    }
                },
            ],
            'total' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],

        ]);
    }
}
