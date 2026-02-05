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



ALTER TABLE customers
MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;

ALTER TABLE sales
MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;

CREATE TABLE emi_installments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    sale_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,

    installment_no INT NOT NULL,
    due_date DATE NOT NULL,

    amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_date DATE NULL,

    status ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY uniq_sale_installment (sale_id, installment_no),
    INDEX idx_sale_status (sale_id, status),
    INDEX idx_customer_status (customer_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `customers` ADD `pharmacy_id` INT NULL AFTER `id`, ADD `pharmacy_branch_id` INT NULL AFTER `pharmacy_id`;
