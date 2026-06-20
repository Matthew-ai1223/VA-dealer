<?php
/**
 * Groq AI client for customer support chat
 * Stage 2: persist chat sessions / lead capture
 */

function groqChat(array $messages): array
{
    $config = appConfig();
    $groq = $config['groq'] ?? [];

    if (empty($groq['enabled'])) {
        return ['success' => false, 'message' => 'AI support is currently disabled.'];
    }

    $apiKey = trim($groq['api_key'] ?? '');
    if ($apiKey === '') {
        return [
            'success' => false,
            'message' => 'AI support is not configured yet. Please contact us on WhatsApp.',
        ];
    }

    $payload = [
        'model'       => $groq['model'] ?? 'llama-3.3-70b-versatile',
        'messages'    => $messages,
        'temperature' => 0.6,
        'max_tokens'  => 800,
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Could not reach AI service: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $err = $data['error']['message'] ?? 'AI service returned an error.';
        return ['success' => false, 'message' => $err];
    }

    $reply = trim($data['choices'][0]['message']['content'] ?? '');
    if ($reply === '') {
        return ['success' => false, 'message' => 'Empty response from AI. Please try again.'];
    }

    return ['success' => true, 'reply' => $reply];
}

function buildSupportSystemPrompt(): string
{
    $config = appConfig();
    $siteName = $config['site_name'];
    $whatsapp = $config['whatsapp_number'];

    $inventoryContext = '';
    try {
        require_once __DIR__ . '/../models/Car.php';
        $carModel = new Car();
        $cars = array_slice($carModel->getAll([], false), 0, 15);
        if (!empty($cars)) {
            $lines = [];
            foreach ($cars as $car) {
                $lines[] = sprintf(
                    '- %s (%s %s, %d) — %s',
                    $car['title'],
                    $car['brand'],
                    $car['model'],
                    $car['year'],
                    $car['price_formatted']
                );
            }
            $inventoryContext = "\n\nCurrent available inventory:\n" . implode("\n", $lines);
        }
    } catch (Exception $e) {
        // Continue without inventory if DB unavailable
    }

    return <<<PROMPT
You are the friendly AI customer support assistant for {$siteName}, a premium pre-owned car dealership in Nigeria.

Your role:
- Help customers find suitable vehicles from our inventory
- Answer questions about pricing, brands, models, and years
- Explain how to browse listings on our website
- Encourage WhatsApp contact for test drives, negotiations, or purchases
- Be professional, concise, and warm

Rules:
- Only discuss cars and dealership services
- If you don't know something, suggest WhatsApp: +{$whatsapp}
- Prices are in Nigerian Naira (₦)
- Never invent cars not in the inventory list
- Keep replies under 120 words unless listing multiple cars
{$inventoryContext}
PROMPT;
}
