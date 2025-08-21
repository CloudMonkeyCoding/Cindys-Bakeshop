-- Adds Face_Image_Path column to User table if it does not already exist
ALTER TABLE User
    ADD COLUMN IF NOT EXISTS Face_Image_Path VARCHAR(255) DEFAULT NULL AFTER Warning_Count;
