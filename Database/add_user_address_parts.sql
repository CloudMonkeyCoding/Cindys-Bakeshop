ALTER TABLE `user`
    ADD COLUMN `Address_Street` varchar(255) DEFAULT NULL AFTER `Address`,
    ADD COLUMN `Address_Barangay` varchar(255) DEFAULT NULL AFTER `Address_Street`,
    ADD COLUMN `Address_City` varchar(150) DEFAULT NULL AFTER `Address_Barangay`,
    ADD COLUMN `Address_Province` varchar(150) DEFAULT NULL AFTER `Address_City`;

UPDATE `user` AS u
LEFT JOIN (
    SELECT
        User_ID,
        normalized,
        TRIM(SUBSTRING_INDEX(normalized, ',', 1)) AS street,
        TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(normalized, ',', 2), ',', -1)) AS barangay,
        TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(normalized, ',', 3), ',', -1)) AS city,
        TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(normalized, ',', 4), ',', -1)) AS province,
        GREATEST(0, CHAR_LENGTH(normalized) - CHAR_LENGTH(REPLACE(normalized, ',', ''))) AS comma_count
    FROM (
        SELECT
            User_ID,
            REPLACE(REPLACE(REPLACE(COALESCE(Address, ''), '\r\n', ','), '\n', ','), '\r', ',') AS normalized
        FROM `user`
    ) AS prepared
) AS addr ON addr.User_ID = u.User_ID
SET
    u.`Address_Street` = NULLIF(addr.street, ''),
    u.`Address_Barangay` = CASE WHEN addr.comma_count >= 1 THEN NULLIF(addr.barangay, '') ELSE NULL END,
    u.`Address_City` = CASE WHEN addr.comma_count >= 2 THEN NULLIF(addr.city, '') ELSE NULL END,
    u.`Address_Province` = CASE WHEN addr.comma_count >= 3 THEN NULLIF(addr.province, '') ELSE NULL END;
