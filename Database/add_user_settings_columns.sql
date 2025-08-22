-- Adds settings columns to User table if they do not already exist
ALTER TABLE User
    ADD COLUMN IF NOT EXISTS Language VARCHAR(50) DEFAULT 'English' AFTER Address,
    ADD COLUMN IF NOT EXISTS Theme VARCHAR(20) DEFAULT 'Light' AFTER Language,
    ADD COLUMN IF NOT EXISTS Notify_Order_Status TINYINT(1) DEFAULT 0 AFTER Theme,
    ADD COLUMN IF NOT EXISTS Notify_Promotions TINYINT(1) DEFAULT 0 AFTER Notify_Order_Status,
    ADD COLUMN IF NOT EXISTS Notify_Feedback TINYINT(1) DEFAULT 0 AFTER Notify_Promotions;
