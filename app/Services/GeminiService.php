<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    private string $apiKey;
    private string $model = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
    }

    /**
     * Extract purchase invoice data from a PDF (base64 encoded).
     * Returns structured array with supplier, invoice_date, invoice_number, items[].
     */
    public function extractInvoice(string $pdfBase64, string $mimeType = 'application/pdf'): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $prompt = <<<'PROMPT'
You are a purchase invoice parser. Extract all product/item information from this invoice PDF.

Return ONLY a valid JSON object (no markdown, no explanation) with this exact structure:
{
  "supplier": "Supplier name or null",
  "invoice_number": "Invoice number or null",
  "invoice_date": "YYYY-MM-DD or null",
  "total_amount": 0.00,
  "items": [
    {
      "product_name": "Product name",
      "variant_name": "Variant info like color/size, or null if none",
      "sku": "SKU or barcode or null",
      "quantity": 1,
      "unit_cost": 0.00,
      "total_cost": 0.00
    }
  ]
}

Rules:
- Each row in the invoice becomes one item in the items array.
- If a product has multiple variants listed separately, keep them as separate items.
- variant_name should capture color, size, or other variation attributes if present.
- All monetary values should be numbers (not strings).
- quantity must be a number.
- If you cannot find a field, use null for strings and 0 for numbers.
PROMPT;

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $pdfBase64,
                                ],
                            ],
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0,
                    'response_mime_type' => 'application/json',
                ],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        $content = $response->json('candidates.0.content.parts.0.text', '');

        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', trim($content));

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse Gemini response as JSON: ' . $content);
        }

        return $data;
    }
}
