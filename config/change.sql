ALTER TABLE `medicine_types` ADD `pharmacy_id` INT NULL AFTER `id`, ADD `pharmacy_branch_id` INT NULL AFTER `pharmacy_id`;
ALTER TABLE `medicine_companies` CHANGE `updated_at` `updated_at` TIMESTAMP NULL DEFAULT NULL; 
ALTER TABLE `medicine_companies` ADD `pharmacy_id` INT NULL AFTER `id`, ADD `pharmacy_branch_id` INT NULL AFTER `pharmacy_id`;
ALTER TABLE `brands` ADD `pharmacy_id` INT NULL AFTER `id`, ADD `pharmacy_branch_id` INT NULL AFTER `pharmacy_id`;

ALTER TABLE `users` ADD `is_admin` INT NULL DEFAULT '0' AFTER `deleted_at`; 
ALTER TABLE `pharmacies` ADD `deleted_at` TIMESTAMP NULL AFTER `updated_at`; 
ALTER TABLE `pharmacy_branches` ADD `deleted_at` TIMESTAMP NULL AFTER `branch_config`; 

ALTER DATABASE showroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE medicine_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE medicine_companies CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `order_items` CHANGE `batch_no` `batch_no` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'BAT-321';
ALTER TABLE `cart_items` CHANGE `batch_no` `batch_no` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'BAT-321';
ALTER TABLE `sale_items` CHANGE `batch_no` `batch_no` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'BAT-321';

UPDATE `order_items` SET `batch_no` = 'BAT-321' WHERE 1;
UPDATE `cart_items` SET `batch_no` = 'BAT-321' WHERE 1;
UPDATE `sale_items` SET `batch_no` = 'BAT-321' WHERE 1;
UPDATE `products` SET `batch_no` = 'BAT-321' WHERE 1;


ALTER TABLE `cart_items` ADD `serial_no` JSON NULL AFTER `discount`;
ALTER TABLE `sale_items` ADD `serial_no` JSON NULL AFTER `power`;


ALTER TABLE `products` ADD `unit_price` FLOAT(15,2) NOT NULL DEFAULT '0.0' AFTER `tp`;
-- ALTER TABLE `order_items` CHANGE `unit_price` `unit_price` FLOAT(15,2) NULL DEFAULT '0.00' COMMENT 'merchant price without TC';
