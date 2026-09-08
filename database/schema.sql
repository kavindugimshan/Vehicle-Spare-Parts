-- =====================================================================
-- VSPMS - Vehicle Spare Parts Management System
-- database/schema.sql
--
-- FROZEN after the initial commit (see docs/PROJECT_BRIEF.md, Section 3,
-- Rule 3). If a table change is needed later, add a new numbered file in
-- database/migrations/ instead of editing this one.
--
-- 15 tables, created in dependency order so foreign keys always resolve.
-- Engine: InnoDB. Charset: utf8mb4 throughout.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS vspms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vspms_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. admin
-- ---------------------------------------------------------------------
CREATE TABLE admin (
    adminID       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    passwordHash  VARCHAR(255) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    fullName      VARCHAR(100) NOT NULL,
    isActive      TINYINT(1)   NOT NULL DEFAULT 1,
    createdAt     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. registered_user
-- ---------------------------------------------------------------------
CREATE TABLE registered_user (
    userID        INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    passwordHash  VARCHAR(255) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    phone         VARCHAR(20)  NULL,
    address       VARCHAR(255) NULL,
    registeredAt  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    isVerified    TINYINT(1)   NOT NULL DEFAULT 0,
    resetToken    VARCHAR(255) NULL,
    resetExpires  DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. guest_user
-- ---------------------------------------------------------------------
CREATE TABLE guest_user (
    sessionID  VARCHAR(64) PRIMARY KEY,
    visitedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. category (self-referencing for sub-categories)
-- ---------------------------------------------------------------------
CREATE TABLE category (
    categoryID       INT AUTO_INCREMENT PRIMARY KEY,
    parentCategoryID INT NULL,
    categoryName     VARCHAR(100) NOT NULL,
    description      TEXT NULL,
    CONSTRAINT fk_category_parent FOREIGN KEY (parentCategoryID)
        REFERENCES category(categoryID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. brand
-- ---------------------------------------------------------------------
CREATE TABLE brand (
    brandID       INT AUTO_INCREMENT PRIMARY KEY,
    brandName     VARCHAR(100) NOT NULL UNIQUE,
    isAuthorized  TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. country
-- ---------------------------------------------------------------------
CREATE TABLE country (
    countryID       INT AUTO_INCREMENT PRIMARY KEY,
    countryName     VARCHAR(100) NOT NULL,
    countryCode     CHAR(3) NOT NULL,
    importDutyRate  DECIMAL(5,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 7. spare_part
-- ---------------------------------------------------------------------
CREATE TABLE spare_part (
    partID         INT AUTO_INCREMENT PRIMARY KEY,
    categoryID     INT NOT NULL,
    brandID        INT NOT NULL,
    countryID      INT NOT NULL,
    adminID        INT NOT NULL,
    partName       VARCHAR(150) NOT NULL,
    partNumber     VARCHAR(50)  NOT NULL,
    description    TEXT NULL,
    price          DECIMAL(12,2) NOT NULL,
    size           VARCHAR(50)  NULL,
    stockQty       INT NOT NULL DEFAULT 0,
    minStockLevel  INT NOT NULL DEFAULT 0,
    imageURL       VARCHAR(255) NULL,
    isActive       TINYINT(1) NOT NULL DEFAULT 1,
    createdAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_part_category FOREIGN KEY (categoryID) REFERENCES category(categoryID),
    CONSTRAINT fk_part_brand    FOREIGN KEY (brandID)    REFERENCES brand(brandID),
    CONSTRAINT fk_part_country  FOREIGN KEY (countryID)  REFERENCES country(countryID),
    CONSTRAINT fk_part_admin    FOREIGN KEY (adminID)    REFERENCES admin(adminID),
    INDEX idx_part_active (isActive),
    INDEX idx_part_name (partName),
    INDEX idx_part_number (partNumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 8. search_log
-- ---------------------------------------------------------------------
CREATE TABLE search_log (
    searchID       INT AUTO_INCREMENT PRIMARY KEY,
    userID         INT NULL,
    sessionID      VARCHAR(64) NULL,
    searchKeyword  VARCHAR(255) NULL,
    filterCategory INT NULL,
    filterBrand    INT NULL,
    filterCountry  INT NULL,
    filterSize     VARCHAR(50) NULL,
    priceMin       DECIMAL(12,2) NULL,
    priceMax       DECIMAL(12,2) NULL,
    resultsCount   INT NOT NULL DEFAULT 0,
    searchedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_search_user    FOREIGN KEY (userID)    REFERENCES registered_user(userID) ON DELETE SET NULL,
    CONSTRAINT fk_search_session FOREIGN KEY (sessionID) REFERENCES guest_user(sessionID) ON DELETE SET NULL,
    CONSTRAINT fk_search_category FOREIGN KEY (filterCategory) REFERENCES category(categoryID) ON DELETE SET NULL,
    CONSTRAINT fk_search_brand    FOREIGN KEY (filterBrand)    REFERENCES brand(brandID) ON DELETE SET NULL,
    CONSTRAINT fk_search_country  FOREIGN KEY (filterCountry)  REFERENCES country(countryID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 9. cart
-- ---------------------------------------------------------------------
CREATE TABLE cart (
    cartID     INT AUTO_INCREMENT PRIMARY KEY,
    userID     INT NOT NULL UNIQUE,
    createdAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (userID) REFERENCES registered_user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 10. cart_item
-- ---------------------------------------------------------------------
CREATE TABLE cart_item (
    cartItemID  INT AUTO_INCREMENT PRIMARY KEY,
    cartID      INT NOT NULL,
    partID      INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    addedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cartitem_cart FOREIGN KEY (cartID) REFERENCES cart(cartID) ON DELETE CASCADE,
    CONSTRAINT fk_cartitem_part FOREIGN KEY (partID) REFERENCES spare_part(partID),
    UNIQUE KEY uq_cart_part (cartID, partID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 11. orders (plural - ORDER is a reserved SQL word)
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    orderID          INT AUTO_INCREMENT PRIMARY KEY,
    userID           INT NOT NULL,
    orderDate        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    totalAmount      DECIMAL(12,2) NOT NULL,
    discountAmount   DECIMAL(12,2) NOT NULL DEFAULT 0,
    taxAmount        DECIMAL(12,2) NOT NULL DEFAULT 0,
    finalAmount      DECIMAL(12,2) NOT NULL,
    status           ENUM('Pending','Confirmed','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
    shippingAddress  VARCHAR(255) NOT NULL,
    trackingNumber   VARCHAR(100) NULL,
    deliveryDate     DATE NULL,
    CONSTRAINT fk_order_user FOREIGN KEY (userID) REFERENCES registered_user(userID),
    INDEX idx_order_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 12. order_item
-- ---------------------------------------------------------------------
CREATE TABLE order_item (
    orderItemID  INT AUTO_INCREMENT PRIMARY KEY,
    orderID      INT NOT NULL,
    partID       INT NOT NULL,
    quantity     INT NOT NULL,
    unitPrice    DECIMAL(12,2) NOT NULL,
    subtotal     DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_orderitem_order FOREIGN KEY (orderID) REFERENCES orders(orderID) ON DELETE CASCADE,
    CONSTRAINT fk_orderitem_part  FOREIGN KEY (partID)  REFERENCES spare_part(partID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 13. payment_gateway
-- ---------------------------------------------------------------------
CREATE TABLE payment_gateway (
    gatewayID           INT AUTO_INCREMENT PRIMARY KEY,
    gatewayName         VARCHAR(100) NOT NULL,
    apiEndpoint         VARCHAR(255) NULL,
    isActive            TINYINT(1) NOT NULL DEFAULT 1,
    transactionFeeRate  DECIMAL(5,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 14. payment
-- ---------------------------------------------------------------------
CREATE TABLE payment (
    paymentID       INT AUTO_INCREMENT PRIMARY KEY,
    orderID         INT NOT NULL UNIQUE,
    gatewayID       INT NOT NULL,
    transactionID   VARCHAR(150) NULL,
    amount          DECIMAL(12,2) NOT NULL,
    status          ENUM('Pending','Success','Failed','Refunded') NOT NULL DEFAULT 'Pending',
    paidAt          DATETIME NULL,
    refundAmount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    receiptURL      VARCHAR(255) NULL,
    CONSTRAINT fk_payment_order   FOREIGN KEY (orderID)   REFERENCES orders(orderID) ON DELETE CASCADE,
    CONSTRAINT fk_payment_gateway FOREIGN KEY (gatewayID) REFERENCES payment_gateway(gatewayID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 15. product_request
-- ---------------------------------------------------------------------
CREATE TABLE product_request (
    requestID        INT AUTO_INCREMENT PRIMARY KEY,
    userID           INT NOT NULL,
    adminID          INT NULL,
    fulfilledPartID  INT NULL,
    partName         VARCHAR(150) NOT NULL,
    partDescription  TEXT NULL,
    preferredBrand   VARCHAR(100) NULL,
    preferredCountry VARCHAR(100) NULL,
    size             VARCHAR(50) NULL,
    budgetMin        DECIMAL(12,2) NULL,
    budgetMax        DECIMAL(12,2) NULL,
    quantity         INT NOT NULL DEFAULT 1,
    status           ENUM('Pending','In Progress','Fulfilled','Rejected') NOT NULL DEFAULT 'Pending',
    requestedAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    adminNotes       TEXT NULL,
    CONSTRAINT fk_request_user    FOREIGN KEY (userID) REFERENCES registered_user(userID),
    CONSTRAINT fk_request_admin   FOREIGN KEY (adminID) REFERENCES admin(adminID) ON DELETE SET NULL,
    CONSTRAINT fk_request_part    FOREIGN KEY (fulfilledPartID) REFERENCES spare_part(partID) ON DELETE SET NULL,
    INDEX idx_request_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
