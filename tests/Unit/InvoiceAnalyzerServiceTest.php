<?php

namespace App\Tests\Unit;

use App\Service\InvoiceAnalyzerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class InvoiceAnalyzerServiceTest extends TestCase
{
    public function testAnalyzeParsesGeminiJsonPayload(): void
    {
        $fakeGeminiResponse = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'date' => '2026-11-18',
                                    'garage' => 'Euromaster Dreux',
                                    'mileage' => 133463,
                                    'totalTTC' => 149.90,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $mockClient = new MockHttpClient(new MockResponse($fakeGeminiResponse));
        $service = new InvoiceAnalyzerService($mockClient, 'fake-api-key');

        $tmpFile = tempnam(sys_get_temp_dir(), 'invoice_test_');
        file_put_contents($tmpFile, 'fake-pdf-content');
        $uploadedFile = new UploadedFile($tmpFile, 'facture.pdf', 'application/pdf', null, true);

        $result = $service->analyze($uploadedFile);

        $this->assertSame('2026-11-18', $result['date']);
        $this->assertSame('Euromaster Dreux', $result['garage']);
        $this->assertSame(133463, $result['mileage']);
        $this->assertSame(149.90, $result['totalTTC']);

        @unlink($tmpFile);
    }
}
