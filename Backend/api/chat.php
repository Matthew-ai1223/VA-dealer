<?php
/**
 * AI Customer Support Chat API (Groq-powered)
 * POST /Backend/api/chat.php
 * Body: { "messages": [{ "role": "user"|"assistant", "content": "..." }] }
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/groq.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessages = $input['messages'] ?? [];

if (!is_array($userMessages) || empty($userMessages)) {
    jsonResponse(['success' => false, 'message' => 'Messages required'], 422);
}

// Limit conversation length and validate roles
$allowedRoles = ['user', 'assistant'];
$sanitized = [];
foreach (array_slice($userMessages, -10) as $msg) {
    if (!is_array($msg) || empty($msg['content'])) {
        continue;
    }
    $role = in_array($msg['role'] ?? '', $allowedRoles, true) ? $msg['role'] : 'user';
    $sanitized[] = [
        'role'    => $role,
        'content' => mb_substr(trim(strip_tags($msg['content'])), 0, 1000),
    ];
}

if (empty($sanitized)) {
    jsonResponse(['success' => false, 'message' => 'No valid messages'], 422);
}

$messages = array_merge(
    [['role' => 'system', 'content' => buildSupportSystemPrompt()]],
    $sanitized
);

$result = groqChat($messages);

if (!$result['success']) {
    jsonResponse([
        'success' => false,
        'message' => $result['message'],
        'fallback_whatsapp' => 'https://wa.me/' . appConfig()['whatsapp_number'],
    ], 503);
}

jsonResponse([
    'success' => true,
    'reply'   => $result['reply'],
]);
