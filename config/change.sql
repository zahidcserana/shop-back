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


CREATE TABLE emi_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    down_payment DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    emi_amount DECIMAL(12,2) NOT NULL,
    total_installments INT NOT NULL,
    start_date DATE NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sale_id (sale_id),
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE emi_installments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emi_plan_id BIGINT UNSIGNED NOT NULL,
    installment_no INT NOT NULL,
    due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_date DATE NULL,
    status ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',

    INDEX idx_emi_plan_id (emi_plan_id),
    UNIQUE KEY uniq_plan_installment (emi_plan_id, installment_no),

    CONSTRAINT fk_emi_installments_plan
        FOREIGN KEY (emi_plan_id)
        REFERENCES emi_plans(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
