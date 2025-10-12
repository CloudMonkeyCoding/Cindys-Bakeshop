<?php

if (!function_exists('record_audit_log')) {
    function record_audit_log($pdo, string $eventType, string $description, array $options = []): void
    {
        if (!$pdo instanceof PDO) {
            return;
        }

        $eventType = trim($eventType);
        if ($eventType === '') {
            return;
        }

        $description = trim($description);
        if ($description === '') {
            $description = $eventType;
        }

        $actorId = null;
        if (isset($options['actor_id']) && is_numeric($options['actor_id'])) {
            $actorId = (int) $options['actor_id'];
        }

        $actorEmail = null;
        if (!empty($options['actor_email'])) {
            $actorEmail = (string) $options['actor_email'];
        }

        $source = null;
        if (!empty($options['source'])) {
            $source = (string) $options['source'];
        }

        $metadata = [];
        if (isset($options['metadata']) && is_array($options['metadata'])) {
            $metadata = $options['metadata'];
        }

        $filteredMetadata = [];
        foreach ($metadata as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                continue;
            }
            $filteredMetadata[$key] = $value;
        }

        $metadataJson = null;
        if ($filteredMetadata) {
            $encoded = json_encode($filteredMetadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $metadataJson = $encoded;
            }
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO audit_log (Event_Type, Description, Actor_User_ID, Actor_Email, Source, Metadata_JSON)
                                   VALUES (:event_type, :description, :actor_id, :actor_email, :source, :metadata_json)');
            $stmt->bindValue(':event_type', $eventType, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);

            if ($actorId === null) {
                $stmt->bindValue(':actor_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':actor_id', $actorId, PDO::PARAM_INT);
            }

            if ($actorEmail === null) {
                $stmt->bindValue(':actor_email', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':actor_email', $actorEmail, PDO::PARAM_STR);
            }

            if ($source === null) {
                $stmt->bindValue(':source', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':source', $source, PDO::PARAM_STR);
            }

            if ($metadataJson === null) {
                $stmt->bindValue(':metadata_json', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':metadata_json', $metadataJson, PDO::PARAM_STR);
            }

            $stmt->execute();
        } catch (Throwable $exception) {
            error_log('Failed to record audit log: ' . $exception->getMessage());
        }
    }
}

if (!function_exists('record_api_call')) {
    function record_api_call($pdo, string $endpoint, array $options = []): void
    {
        if (!$pdo instanceof PDO) {
            return;
        }

        $action = '';
        if (isset($options['action'])) {
            $actionValue = $options['action'];
            if (is_string($actionValue) || is_numeric($actionValue)) {
                $action = trim((string) $actionValue);
            }
        }

        if ($action === '' && isset($_GET['action'])) {
            $action = is_string($_GET['action']) ? trim($_GET['action']) : $action;
        }

        if ($action === '' && isset($_POST['action'])) {
            $action = is_string($_POST['action']) ? trim($_POST['action']) : $action;
        }

        $description = "API call received for {$endpoint}";
        if ($action !== '') {
            $description .= " (action: {$action})";
        }

        $metadata = [
            'endpoint' => $endpoint,
            'action' => $action !== '' ? $action : null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        if (!empty($_GET)) {
            $metadata['query_keys'] = array_values(array_unique(array_map('strval', array_keys($_GET))));
        }

        if (!empty($_POST)) {
            $metadata['body_keys'] = array_values(array_unique(array_map('strval', array_keys($_POST))));
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $metadata['request_uri'] = $_SERVER['REQUEST_URI'];
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $metadata['xhr'] = $_SERVER['HTTP_X_REQUESTED_WITH'];
        }

        $extraMetadata = [];
        if (isset($options['metadata']) && is_array($options['metadata'])) {
            $extraMetadata = $options['metadata'];
        }

        $metadata = array_merge($metadata, $extraMetadata);

        $filteredMetadata = [];
        foreach ($metadata as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    continue;
                }
                $filteredMetadata[$key] = $trimmed;
                continue;
            }

            if (is_array($value)) {
                $filteredMetadata[$key] = array_values(array_filter($value, static function ($entry) {
                    if ($entry === null) {
                        return false;
                    }
                    if (is_string($entry)) {
                        return trim($entry) !== '';
                    }
                    return $entry !== '';
                }));
                if ($filteredMetadata[$key] === []) {
                    unset($filteredMetadata[$key]);
                }
                continue;
            }

            $filteredMetadata[$key] = $value;
        }

        $actorId = null;
        if (isset($options['actor_id']) && is_numeric($options['actor_id'])) {
            $actorId = (int) $options['actor_id'];
        }

        $actorEmail = null;
        if (!empty($options['actor_email'])) {
            $actorEmail = (string) $options['actor_email'];
        }

        $source = $options['source'] ?? ('api:' . $endpoint);

        record_audit_log($pdo, 'api_call', $description, [
            'actor_id' => $actorId,
            'actor_email' => $actorEmail,
            'source' => $source,
            'metadata' => $filteredMetadata,
        ]);
    }
}

if (!function_exists('fetch_audit_logs')) {
    function fetch_audit_logs($pdo, int $limit = 100): array
    {
        if (!$pdo instanceof PDO) {
            return [];
        }

        $limit = max(1, min($limit, 500));

        try {
            $stmt = $pdo->prepare('SELECT Audit_Log_ID, Event_Type, Description, Actor_User_ID, Actor_Email, Source, Metadata_JSON, Created_At
                                   FROM audit_log
                                   ORDER BY Created_At DESC, Audit_Log_ID DESC
                                   LIMIT :limit');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            error_log('Failed to fetch audit logs: ' . $exception->getMessage());
            return [];
        }

        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $metadata = [];
            if (!empty($row['Metadata_JSON'])) {
                $decoded = json_decode($row['Metadata_JSON'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $metadata = $decoded;
                }
            }
            $row['Metadata'] = $metadata;
        }
        unset($row);

        return $rows;
    }
}
