CREATE TABLE IF NOT EXISTS audit_log (
    Audit_Log_ID INT AUTO_INCREMENT PRIMARY KEY,
    Event_Type VARCHAR(100) NOT NULL,
    Description TEXT NOT NULL,
    Actor_User_ID INT NULL,
    Actor_Email VARCHAR(255) NULL,
    Source VARCHAR(100) NULL,
    Metadata_JSON TEXT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE audit_log
    ADD INDEX IF NOT EXISTS idx_audit_log_created_at (Created_At),
    ADD INDEX IF NOT EXISTS idx_audit_log_event_type (Event_Type),
    ADD INDEX IF NOT EXISTS idx_audit_log_actor (Actor_User_ID);
