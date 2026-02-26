
//Warehouse

CREATE TABLE warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id int NOT NULL,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    INDEX idx_pharmacy (pharmacy_id),
    CONSTRAINT fk_warehouses_pharmacy
        FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE warehouse_stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY uk_warehouse_medicine (warehouse_id, medicine_id),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_medicine (medicine_id),

    CONSTRAINT fk_ws_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE stock_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id int NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    pharmacy_branch_id int NOT NULL,

    reference_no VARCHAR(50) NOT NULL,
    status ENUM('PENDING','APPROVED','SENT','RECEIVED','CANCELLED') DEFAULT 'PENDING',

    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    INDEX idx_pharmacy (pharmacy_id),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_branch (pharmacy_branch_id),
    INDEX idx_status (status),
    UNIQUE KEY uk_reference (reference_no),

    CONSTRAINT fk_st_pharmacy
        FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id),

    CONSTRAINT fk_st_warehouse
        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),

    CONSTRAINT fk_st_branch
        FOREIGN KEY (pharmacy_branch_id) REFERENCES pharmacy_branches(id)
) ENGINE=InnoDB;

CREATE TABLE stock_transfer_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_transfer_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    batch_no VARCHAR(255) NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_transfer (stock_transfer_id),
    INDEX idx_medicine (medicine_id),

    CONSTRAINT fk_sti_transfer
        FOREIGN KEY (stock_transfer_id)
        REFERENCES stock_transfers(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

