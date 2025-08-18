<?php
require_once 'db_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo 'Database connection failed';
    exit;
}
require_once 'order_functions.php';
require_once 'order_item_functions.php';
require_once 'user_functions.php';

$orders = getAllOrders($pdo);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Order ID', 'Customer', 'Items', 'Total', 'Status']);

foreach ($orders as $order) {
    $user = getUserById($pdo, $order['User_ID']);
    $items = getOrderItemsByOrderId($pdo, $order['Order_ID']);
    $itemCount = count($items);
    $total = calculateOrderTotal($pdo, $order['Order_ID']);

    fputcsv($output, [
        sprintf('%05d', $order['Order_ID']),
        $user['Name'] ?? 'User ' . $order['User_ID'],
        $itemCount,
        number_format($total ?? 0, 2, '.', ''),
        $order['Status']
    ]);
}

fclose($output);
exit;
?>
