<?php
function startJsonResponse(bool $withSession = false): void
{
    if ($withSession && session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
}

/**
 * Decode a JSON request body when the incoming request advertises JSON content.
 *
 * @return array<string,mixed>
 */
function getJsonRequestPayload(): array
{
    static $decoded;

    if ($decoded !== null) {
        return $decoded;
    }

    $decoded = [];
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return $decoded;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (!is_string($contentType) || stripos($contentType, 'application/json') === false) {
        return $decoded;
    }

    $rawInput = file_get_contents('php://input');
    if ($rawInput === false || trim($rawInput) === '') {
        return $decoded;
    }

    $data = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        $decoded = $data;
    }

    return $decoded;
}

/**
 * Provide a unified payload combining traditional form data with decoded JSON bodies.
 *
 * @return array<string,mixed>
 */
function getRequestPayload(): array
{
    static $payload;

    if ($payload !== null) {
        return $payload;
    }

    $postData = $_POST;
    if (!is_array($postData)) {
        $postData = [];
    }

    $jsonPayload = getJsonRequestPayload();
    if (!empty($jsonPayload)) {
        foreach ($jsonPayload as $key => $value) {
            if (is_string($key)) {
                $_POST[$key] = $value;
            }
        }

        $payload = array_merge($postData, $jsonPayload);
    } else {
        $payload = $postData;
    }

    return $payload;
}

function sendJsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function requirePostRequest(string $errorMessage = 'Invalid request method'): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => $errorMessage], 405);
    }
}

function requireDatabaseConnection(?PDO $pdo): void
{
    if (!$pdo) {
        sendJsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }
}

function requireCsrfToken(string $token): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
    }
}
