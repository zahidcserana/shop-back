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

ALTER TABLE `order_items` CHANGE `batch_no` `batch_no` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT 'BAT-321';
ALTER TABLE `cart_items` CHANGE `batch_no` `batch_no` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT 'BAT-321';
ALTER TABLE `sale_items` CHANGE `batch_no` `batch_no` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT 'BAT-321';

