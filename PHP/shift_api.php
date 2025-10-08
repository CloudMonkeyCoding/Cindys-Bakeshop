<?php
require_once 'db_connect.php';
require_once 'shift_functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function validateDate(?string $date): ?string
{
    if ($date === null || $date === '') {
        return null;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if ($parsed === false) {
        return false;
    }

    return $parsed->format('Y-m-d');
}

function formatShiftRow(array $row): array
{
    return [
        'shift_id' => isset($row['Shift_ID']) ? (int) $row['Shift_ID'] : null,
        'user_id' => isset($row['User_ID']) ? (int) $row['User_ID'] : null,
        'staff_name' => $row['Name'] ?? null,
        'shift_date' => $row['Shift_Date'] ?? null,
        'scheduled_start' => $row['Scheduled_Start'] ?? null,
        'scheduled_end' => $row['Scheduled_End'] ?? null,
        'actual_start' => $row['Actual_Start'] ?? null,
        'actual_end' => $row['Actual_End'] ?? null,
        'status' => $row['Status'] ?? null,
        'notes' => $row['Notes'] ?? null,
    ];
}

switch ($action) {
    case 'list':
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $validatedStart = validateDate($startDate);
        if ($validatedStart === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid start_date. Use YYYY-MM-DD.']);
            break;
        }

        $validatedEnd = validateDate($endDate);
        if ($validatedEnd === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid end_date. Use YYYY-MM-DD.']);
            break;
        }

        $shifts = getShiftSchedules($pdo, $userId, $validatedStart, $validatedEnd);
        $payload = array_map('formatShiftRow', $shifts);

        echo json_encode(['shifts' => $payload]);
        break;

    case 'start_shift':
        $shiftId = (int) ($_POST['shift_id'] ?? $_GET['shift_id'] ?? 0);
        if ($shiftId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'shift_id is required']);
            break;
        }

        if (!startShift($pdo, $shiftId)) {
            http_response_code(409);
            echo json_encode(['error' => 'Unable to start the shift. It may already be in progress.']);
            break;
        }

        echo json_encode(['started' => true]);
        break;

    case 'end_shift':
        $shiftId = (int) ($_POST['shift_id'] ?? $_GET['shift_id'] ?? 0);
        if ($shiftId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'shift_id is required']);
            break;
        }

        if (!endShift($pdo, $shiftId)) {
            http_response_code(409);
            echo json_encode(['error' => 'Unable to end the shift. Start the shift before ending it.']);
            break;
        }

        echo json_encode(['ended' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action']);
}

