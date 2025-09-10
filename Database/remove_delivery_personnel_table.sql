-- Drops the delivery_personnel table and links deliveries directly to users
ALTER TABLE delivery DROP FOREIGN KEY delivery_ibfk_1;
ALTER TABLE delivery ADD CONSTRAINT delivery_ibfk_1 FOREIGN KEY (Delivery_Personnel) REFERENCES user (User_ID);
DROP TABLE IF EXISTS delivery_personnel;
