-- =====================================================================
-- database/seed_core.sql  (Module 1)
--
-- First admin account, the two payment gateway rows, and a couple of
-- sample registered users for testing login / profile / order history
-- before Module 3 exists. Import after schema.sql.
--
-- Seed passwords (bcrypt hashes below, verified against PHP's
-- password_verify() since PHP's crypt() accepts $2a$/$2b$/$2y$ bcrypt
-- variants interchangeably):
--   admin      -> Admin@123
--   john_doe   -> Password123
--   jane_smith -> Password123
-- =====================================================================

USE vspms_db;

INSERT INTO admin (username, passwordHash, email, fullName, isActive) VALUES
('admin', '$2b$10$PbDwcQE2tFNW2XjqdN.CIOk07u65Stq452pS8QkN.DoABKA79b.Sm', 'admin@autopartslanka.lk', 'System Administrator', 1);

INSERT INTO registered_user (username, passwordHash, email, phone, address, isVerified) VALUES
('john_doe',   '$2b$10$XhwPJbbWM2.QxnYQFJqtH.hXErS9/6WWKdLvMpLYGIiyMzKvtVt7e', 'john.doe@example.com',   '0771234567', '12 Galle Road, Colombo 03', 1),
('jane_smith', '$2b$10$XhwPJbbWM2.QxnYQFJqtH.hXErS9/6WWKdLvMpLYGIiyMzKvtVt7e', 'jane.smith@example.com', '0779876543', '45 Kandy Road, Kadawatha', 1);

INSERT INTO cart (userID) VALUES
((SELECT userID FROM registered_user WHERE username = 'john_doe')),
((SELECT userID FROM registered_user WHERE username = 'jane_smith'));

INSERT INTO payment_gateway (gatewayName, apiEndpoint, isActive, transactionFeeRate) VALUES
('PayHere Sandbox', 'https://sandbox.payhere.lk/pay/checkout', 1, 3.30),
('Simulated Card Payment', NULL, 1, 0.00);
