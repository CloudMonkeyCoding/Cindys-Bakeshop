ALTER TABLE `order`
    ADD COLUMN `Special_Instructions` text DEFAULT NULL AFTER `Fulfillment_Type`;
