<?php
// Endpoint to serve Firebase configuration from environment variables.
// Optionally requires a shared secret via the X-Firebase-Config-Token header.

header('Content-Type: application/json');

$expectedToken = getenv('FIREBASE_CONFIG_TOKEN');
if ($expectedToken) {
    $providedToken = $_SERVER['HTTP_X_FIREBASE_CONFIG_TOKEN'] ?? '';
    if (!hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

$config = [
    'apiKey' => getenv('FIREBASE_API_KEY'),
    'authDomain' => getenv('FIREBASE_AUTH_DOMAIN'),
    'projectId' => getenv('FIREBASE_PROJECT_ID'),
    'storageBucket' => getenv('FIREBASE_STORAGE_BUCKET'),
    'messagingSenderId' => getenv('FIREBASE_MESSAGING_SENDER_ID'),
    'appId' => getenv('FIREBASE_APP_ID'),
    'measurementId' => getenv('FIREBASE_MEASUREMENT_ID'),
];

echo json_encode($config);
