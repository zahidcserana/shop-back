ALTER TABLE `sale_items` ADD `return_status` ENUM('RETURN','CHANGE','OK') NULL DEFAULT 'OK' AFTER `server_item_id`;
ALTER TABLE `sale_items` ADD `change_log` TEXT NULL AFTER `return_status`;
ALTER TABLE `sale_items` ADD `updated_by` INT NULL AFTER `change_log`;
ALTER TABLE `sales` ADD `status` ENUM('PENDING','COMPLETE','DUE','CANCEL') NULL DEFAULT 'COMPLETE' AFTER `server_sale_id`;
ALTER TABLE `sales` ADD `due_log` TEXT NULL AFTER `status`;
ALTER TABLE `sales` ADD `updated_by` INT NULL AFTER `created_by`;
ALTER TABLE `sale_items` ADD `product_type` INT NULL AFTER `updated_by`;
ALTER TABLE `cart_items` ADD `product_type` INT NULL AFTER `updated_at`;
ALTER TABLE `products` ADD `is_sync` BOOLEAN NULL DEFAULT FALSE AFTER `pharmacy_id`;
ALTER TABLE `products` ADD `sale_quantity` INT NULL DEFAULT '0' AFTER `quantity`;
ALTER TABLE `cart_items` ADD `tp` FLOAT(15,2) NOT NULL DEFAULT '0' AFTER `unit_price`;
ALTER TABLE `sale_items` ADD `tp` FLOAT(15,2) NOT NULL DEFAULT '0' AFTER `mrp`;
ALTER TABLE `pharmacy_branches` ADD `subscription_period` INT NOT NULL DEFAULT '30' AFTER `branch_model_pharmacy_status`;
ALTER TABLE `pharmacy_branches` ADD `subscription_count` INT NOT NULL DEFAULT '1' AFTER `subscription_period`;
CREATE TABLE `subscriptions` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `pharmacy_id` int(11) NOT NULL,
 `pharmacy_branch_id` int(11) NOT NULL,
 `coupon_code` varchar(255) NOT NULL,
 `coupon_type` enum('1MONTH',' 3MONTH','6MONTH','1YEAR') NOT NULL DEFAULT '1MONTH',
 `apply_date` datetime DEFAULT NULL,
 `status` enum('ACTIVE','INACTIVE','USED') NOT NULL DEFAULT 'ACTIVE',
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

/* 09.12.19 */
ALTER TABLE `sale_items` ADD `deleted_at` TIMESTAMP NULL;
ALTER TABLE `cart_items` ADD `deleted_at` TIMESTAMP NULL;
ALTER TABLE `order_items` ADD `deleted_at` TIMESTAMP NULL;
ALTER TABLE `damage_items` ADD `deleted_at` TIMESTAMP NULL;
ALTER TABLE `notifications` ADD `deleted_at` TIMESTAMP NULL;
ALTER TABLE `products` ADD `deleted_at` TIMESTAMP NULL;
ALTER TABLE `medicines` ADD `deleted_at` TIMESTAMP NULL;

ALTER TABLE `users` ADD `pos_version` INT(1) NULL DEFAULT '1' AFTER `pharmacy_branch_id`;
