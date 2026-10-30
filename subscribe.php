<?php
/**
 * Mex-Ahoy! — Local Backup Endpoint for Newsletter Subscribers (Hostinger + Firebase Hybrid)
 */
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
$source = isset($input['source']) ? trim($input['source']) : 'frontpage_newsletter';
$subscribedAt = isset($input['subscribedAt']) ? trim($input['subscribedAt']) : date('c');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email']);
    exit;
}

$subFile = __DIR__ . '/data/subscribers.json';
if (!file_exists(dirname($subFile))) {
    mkdir(dirname($subFile), 0777, true);
}

$subscribers = [];
if (file_exists($subFile)) {
    $decoded = json_decode(file_get_contents($subFile), true);
    if (is_array($decoded)) {
        $subscribers = $decoded;
    }
}

$subscribers[$email] = [
    'email' => $email,
    'subscribedAt' => $subscribedAt,
    'source' => $source,
    'status' => 'active'
];

file_put_contents($subFile, json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode([
    'ok' => true,
    'email' => $email,
    'total' => count($subscribers)
]);
