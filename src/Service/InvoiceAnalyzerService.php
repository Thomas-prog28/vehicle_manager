<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class InvoiceAnalyzerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%gemini_api_key%')]
        private string $geminiApiKey,
    ) {}

    /**
     * Analyse une facture (PDF ou image) et retourne les champs extraits.
     *
     * @return array{date: ?string, garage: ?string, mileage: ?int, totalTTC: ?float}
     */
    public function analyze(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType() ?? 'application/pdf';
        $base64Data = base64_encode(file_get_contents($file->getPathname()));

        $prompt = <<<PROMPT
Analyse cette facture d'entretien automobile ou moto et extrais uniquement les 4 informations suivantes :
1. "date" : la date de la facture au format strict YYYY-MM-DD (ex: "2026-11-18"). Si introuvable, mets null.
2. "garage" : le nom du garage, de l'enseigne ou du vendeur (ex: "Norauto", "Honda Moto", "Oscaro"). Si introuvable, mets null.
3. "mileage" : le kilométrage du véhicule indiqué sur la facture sous forme de nombre entier uniquement (ex: 65400). Si introuvable, mets null.
4. "totalTTC" : le montant total TTC payé sous forme de nombre décimal avec un point (ex: 149.90). Si introuvable, mets null.

Réponds uniquement avec le JSON demandé, sans texte autour.
PROMPT;

        $host = 'generativelanguage' . '.' . 'googleapis' . '.' . 'com';
        $path = '/v1beta/models/gemini-3.1-flash-lite:generateContent';
        $endpoint = 'https://' . $host . $path;

        $response = $this->httpClient->request(
            'POST',
            $endpoint,
            [
                'timeout' => 60,
                'max_duration' => 60,
                'extra' => [
                    'curl' => [
                        \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4,
                    ],
                ],
                'query' => ['key' => $this->geminiApiKey],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Data,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'thinkingConfig' => [
                            'thinkingBudget' => 0,
                        ]
                    ],
                ],
            ]
        );

        $payload = $response->toArray(false);

        if (isset($payload['error'])) {
            throw new \RuntimeException($payload['error']['message'] ?? 'Erreur API Gemini');
        }
        $rawJson = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $data = json_decode($rawJson, true) ?? [];

        return [
            'date' => isset($data['date']) && is_string($data['date']) ? $data['date'] : null,
            'garage' => isset($data['garage']) && is_string($data['garage']) ? mb_substr($data['garage'], 0, 50) : null,
            'mileage' => isset($data['mileage']) && is_numeric($data['mileage']) ? (int) $data['mileage'] : null,
            'totalTTC' => isset($data['totalTTC']) && is_numeric($data['totalTTC']) ? (float) $data['totalTTC'] : null,
        ];
    }
}
