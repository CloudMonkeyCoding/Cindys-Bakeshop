<?php
/**
 * Shift schedule helper functions.
 */

/**
 * Fetch shift schedules optionally filtered by user and date range.
 *
 * @param PDO      $pdo
 * @param int|null $userId
 * @param string|null $startDate
 * @param string|null $endDate
 *
 * @return array<int, array<string, mixed>>
 */
function getShiftSchedules(PDO $pdo, ?int $userId = null, ?string $startDate = null, ?string $endDate = null): array
{
    $conditions = [];
    $params = [];

    if ($userId !== null) {
        $conditions[] = 's.User_ID = :user_id';
        $params[':user_id'] = $userId;
    }

    if ($startDate !== null) {
        $conditions[] = 's.Shift_Date >= :start_date';
        $params[':start_date'] = $startDate;
    }

    if ($endDate !== null) {
        $conditions[] = 's.Shift_Date <= :end_date';
        $params[':end_date'] = $endDate;
    }

    $where = '';
    if ($conditions) {
        $where = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "
        SELECT s.*, u.Name
        FROM shift_schedule s
        INNER JOIN user u ON s.User_ID = u.User_ID
        $where
        ORDER BY s.Shift_Date ASC, s.Scheduled_Start ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Create a new shift schedule entry.
 */
function createShiftSchedule(PDO $pdo, int $userId, string $shiftDate, string $scheduledStart, string $scheduledEnd, ?string $notes = null): int
{
    $stmt = $pdo->prepare('
        INSERT INTO shift_schedule (User_ID, Shift_Date, Scheduled_Start, Scheduled_End, Notes)
        VALUES (:user_id, :shift_date, :scheduled_start, :scheduled_end, :notes)
    ');

    $stmt->execute([
        ':user_id' => $userId,
        ':shift_date' => $shiftDate,
        ':scheduled_start' => $scheduledStart,
        ':scheduled_end' => $scheduledEnd,
        ':notes' => $notes,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Mark a scheduled shift as started.
 */
function startShift(PDO $pdo, int $shiftId): bool
{
    $stmt = $pdo->prepare('
        UPDATE shift_schedule
        SET Actual_Start = NOW(), Status = "in_progress"
        WHERE Shift_ID = :shift_id AND Actual_Start IS NULL
    ');

    $stmt->execute([':shift_id' => $shiftId]);

    return $stmt->rowCount() > 0;
}

/**
 * Mark a scheduled shift as completed.
 */
function endShift(PDO $pdo, int $shiftId): bool
{
    $stmt = $pdo->prepare('
        UPDATE shift_schedule
        SET Actual_End = NOW(), Status = "completed"
        WHERE Shift_ID = :shift_id AND Actual_Start IS NOT NULL AND Actual_End IS NULL
    ');

    $stmt->execute([':shift_id' => $shiftId]);

    return $stmt->rowCount() > 0;
}

/**
 * Update shift status to missed if the scheduled end time has passed without any activity.
 */
function markShiftMissed(PDO $pdo, int $shiftId): bool
{
    $stmt = $pdo->prepare('
        UPDATE shift_schedule
        SET Status = "missed"
        WHERE Shift_ID = :shift_id AND Actual_Start IS NULL AND Shift_Date < CURRENT_DATE()
    ');

    $stmt->execute([':shift_id' => $shiftId]);

    return $stmt->rowCount() > 0;
}
