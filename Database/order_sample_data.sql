INSERT INTO User (User_ID, Name, Email, Password, Address, Warning_Count) VALUES
    (1, 'Mariel Balanac', 'mariel@example.com', '$2y$10$abcdefghijklmnopqrstuv', '123 Sample St', 0),
    (2, 'Juan Dela Cruz', 'juan@example.com', '$2y$10$abcdefghijklmnopqrstuv', '456 Sample St', 0),
    (3, 'Anna Reyes', 'anna@example.com', '$2y$10$abcdefghijklmnopqrstuv', '789 Sample St', 0);

INSERT INTO `Order` (Order_ID, User_ID, Order_Date, Source, Fulfillment_Type, Status) VALUES
    (1, 1, '2025-07-01', 'online', 'Delivery', 'Pending'),
    (2, 2, '2025-07-02', 'online', 'Delivery', 'Shipped'),
    (3, 3, '2025-07-03', 'online', 'Pick up', 'Delivered');

INSERT INTO Order_Item (Order_Item_ID, Order_ID, Product_ID, Quantity, Subtotal) VALUES
    (1, 1, 23, 1, 699.00),
    (2, 2, 8, 1, 250.00),
    (3, 3, 16, 1, 320.00);
