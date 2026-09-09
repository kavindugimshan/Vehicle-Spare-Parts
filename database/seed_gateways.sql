-- =====================================================================
-- database/seed_gateways.sql  (Module 3)
--
-- The two payment gateway rows PayHere Sandbox and Simulated Card
-- Payment.
--
-- NOTE: database/seed_core.sql (Module 1) already inserts these same
-- two rows, so this file's inserts are guarded with WHERE NOT EXISTS -
-- safe to import whether or not seed_core.sql already ran. See
-- docs/module1.md and docs/module3.md for why the brief's file table
-- assigns "the two payment gateway rows" to both files.
-- =====================================================================

USE vspms_db;

INSERT INTO payment_gateway (gatewayName, apiEndpoint, isActive, transactionFeeRate)
SELECT 'PayHere Sandbox', 'https://sandbox.payhere.lk/pay/checkout', 1, 3.30
WHERE NOT EXISTS (SELECT 1 FROM payment_gateway WHERE gatewayName = 'PayHere Sandbox');

INSERT INTO payment_gateway (gatewayName, apiEndpoint, isActive, transactionFeeRate)
SELECT 'Simulated Card Payment', NULL, 1, 0.00
WHERE NOT EXISTS (SELECT 1 FROM payment_gateway WHERE gatewayName = 'Simulated Card Payment');
