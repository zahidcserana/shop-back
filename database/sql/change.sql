ALTER TABLE `medicine_companies` ADD `pharmacy_id` INT NULL AFTER `id`; 

ALTER TABLE `cart_items` ADD `free_quantity` INT NULL DEFAULT '0' AFTER `quantity`;
