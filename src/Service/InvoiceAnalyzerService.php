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

    /**
     * Analyse une attestation ou un échéancier d'assurance.
     *
     * @return array{insurer: ?string, contractNumber: ?string, startDate: ?string, endDate: ?string, premium: ?float, totalAmount: ?float}
     */
    public function analyzeInsurance(UploadedFile $file, ?string $vehiclePlate = null, ?string $vehicleLabel = null): array
    {
        $mimeType = $file->getMimeType() ?? 'application/pdf';
        $base64Data = base64_encode(file_get_contents($file->getPathname()));

        $plateNoDash = $vehiclePlate ? str_replace(['-', ' '], '', $vehiclePlate) : '';

        $targetInstruction = $vehiclePlate
            ? "ATTENTION : Ce document contient plusieurs contrats ou plusieurs véhicules (par exemple une voiture ET une moto).\nTu dois extraire UNIQUEMENT la cotisation du véhicule suivant :\n- Type / Modèle : {$vehicleLabel}\n- Immatriculation : {$vehiclePlate} (peut aussi être écrite {$plateNoDash} ou avec des espaces).\nIgnore les cotisations des autres véhicules !"
            : "";

        $prompt = <<<PROMPT
Analyse cet avis d'échéance ou attestation d'assurance multi-pages.
{$targetInstruction}

Extrais uniquement les 6 informations suivantes :
1. "insurer" : le nom de la compagnie d'assurance (ex: "MACIF", "MAIF", "Allianz", "AXA"). Si introuvable, mets null.
2. "contractNumber" : le numéro de contrat spécifique à ce véhicule OU le numéro de sociétaire / client global si les véhicules sont regroupés sous un même numéro de sociétaire. Si introuvable, mets null.
3. "startDate" : la date de début de période au format strict YYYY-MM-DD. Si introuvable, mets null.
4. "endDate" : la date de fin de période ou d'échéance au format strict YYYY-MM-DD. Si introuvable, mets null.
5. "premium" : le montant TTC de la cotisation annuelle UNIQUEMENT pour le véhicule {$vehicleLabel} immatriculé {$vehiclePlate}. Vérifie bien que ce montant correspond à la section de {$vehicleLabel} / {$vehiclePlate} et non à un autre véhicule du foyer. Nombre décimal avec un point (ex: 245.30).
6. "totalAmount" : le montant total global TTC à payer pour l'ensemble de l'avis d'échéance (tous contrats confondus). Nombre décimal avec un point (ex: 968.55).

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
                        'response_schema' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'insurer' => ['type' => 'STRING', 'nullable' => true],
                                'contractNumber' => ['type' => 'STRING', 'nullable' => true],
                                'startDate' => ['type' => 'STRING', 'nullable' => true],
                                'endDate' => ['type' => 'STRING', 'nullable' => true],
                                'premium' => ['type' => 'NUMBER', 'nullable' => true],
                                'totalAmount' => ['type' => 'NUMBER', 'nullable' => true],
                            ],
                        ],
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
            'insurer' => isset($data['insurer']) && is_string($data['insurer']) ? mb_substr($data['insurer'], 0, 100) : null,
            'contractNumber' => isset($data['contractNumber']) && is_string($data['contractNumber']) ? mb_substr($data['contractNumber'], 0, 40) : null,
            'startDate' => isset($data['startDate']) && is_string($data['startDate']) ? $data['startDate'] : null,
            'endDate' => isset($data['endDate']) && is_string($data['endDate']) ? $data['endDate'] : null,
            'premium' => isset($data['premium']) && is_numeric($data['premium']) ? (float) $data['premium'] : null,
            'totalAmount' => isset($data['totalAmount']) && is_numeric($data['totalAmount']) ? (float) $data['totalAmount'] : null,
        ];
    }

    /**
     * Analyse simultanément la photo de l'écran de pompe et la photo du compteur kilométrique.
     *
     * @return array{liters: ?float, priceLiter: ?float, totalPrice: ?float, mileage: ?int, stationName: ?string}
     */
    public function analyzeFuelPhotos(UploadedFile $pumpPhoto, UploadedFile $odometerPhoto, ?string $gpsLocation = null): array
    {
        $pumpMime = $pumpPhoto->getMimeType() ?? 'image/jpeg';
        $pumpBase64 = base64_encode(file_get_contents($pumpPhoto->getPathname()));

        $odoMime = $odometerPhoto->getMimeType() ?? 'image/jpeg';
        $odoBase64 = base64_encode(file_get_contents($odometerPhoto->getPathname()));

        $gpsHint = $gpsLocation
            ? "Position GPS du véhicule au moment du plein : {$gpsLocation}. Utilise cette localisation (et tout logo visible sur la pompe) pour déduire le nom de la station et la ville dans 'stationName'."
            : "Déduis le nom de la station s'il est visible sur la photo, sinon mets null.";

        $prompt = <<<PROMPT
Tu reçois 2 photos prises lors d'un plein de carburant :
- Image 1 : l'afficheur de la pompe à carburant.
- Image 2 : le tableau de bord / compteur kilométrique du véhicule.

{$gpsHint}

Extrais uniquement les 5 informations suivantes :
1. "liters" : la quantité de carburant distribuée en litres (nombre décimal avec un point, ex: 42.50). Si illisible, mets null.
2. "priceLiter" : le prix au litre affiché sur la pompe (nombre décimal à 3 chiffres après la virgule, ex: 1.749). Si illisible, mets null.
3. "totalPrice" : le montant total à payer en euros affiché sur la pompe (nombre décimal avec un point, ex: 74.33). Si illisible, mets null.
4. "mileage" : le kilométrage totalisateur lu sur le compteur (nombre entier uniquement, ex: 133890 — ne prends pas le compteur journalier trip). Si illisible, mets null.
5. "stationName" : le nom ou l'enseigne de la station + ville (ex: "TotalEnergies - Dreux").

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
                                ['inline_data' => ['mime_type' => $pumpMime, 'data' => $pumpBase64]],
                                ['inline_data' => ['mime_type' => $odoMime, 'data' => $odoBase64]],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'response_schema' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'liters' => ['type' => 'NUMBER', 'nullable' => true],
                                'priceLiter' => ['type' => 'NUMBER', 'nullable' => true],
                                'totalPrice' => ['type' => 'NUMBER', 'nullable' => true],
                                'mileage' => ['type' => 'INTEGER', 'nullable' => true],
                                'stationName' => ['type' => 'STRING', 'nullable' => true],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $payload = $response->toArray(false);
        if (isset($payload['error'])) {
            throw new \RuntimeException($payload['error']['message'] ?? 'Erreur API Gemini');
        }

        $firstCandidate = $payload['candidates'][0] ?? [];
        $firstPart = $firstCandidate['content']['parts'][0] ?? [];
        $rawJson = $firstPart['text'] ?? '{}';
        $data = json_decode($rawJson, true) ?? [];

        return [
            'liters' => isset($data['liters']) && is_numeric($data['liters']) ? (float) $data['liters'] : null,
            'priceLiter' => isset($data['priceLiter']) && is_numeric($data['priceLiter']) ? (float) $data['priceLiter'] : null,
            'totalPrice' => isset($data['totalPrice']) && is_numeric($data['totalPrice']) ? (float) $data['totalPrice'] : null,
            'mileage' => isset($data['mileage']) && is_numeric($data['mileage']) ? (int) $data['mileage'] : null,
            'stationName' => isset($data['stationName']) && is_string($data['stationName']) ? mb_substr($data['stationName'], 0, 100) : null,
        ];
    }
}
