<?php
// Functions for handling notifications

function addNotification($pdo, $type, $message, $referenceId = null) {
    $stmt = $pdo->prepare(
        "INSERT INTO Notification (Type, Message, Reference_ID)
         VALUES (:type, :message, :reference_id)"
    );
    $stmt->execute([
        ':type' => $type,
        ':message' => $message,
        ':reference_id' => $referenceId
    ]);
    return $pdo->lastInsertId();
}

function getAllNotifications($pdo) {
    $stmt = $pdo->query(
        "SELECT Notification_ID, Type, Message, Is_Read, Created_At
         FROM Notification
         ORDER BY Created_At DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
