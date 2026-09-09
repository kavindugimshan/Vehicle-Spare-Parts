-- =====================================================================
-- database/seed_catalogue.sql  (Module 2)
--
-- 8 top-level categories (15 sub-categories total), 12 brands, 10
-- countries and 46 realistic spare parts. Import after schema.sql and
-- seed_core.sql (spare_part.adminID references the admin account seeded
-- there).
--
-- Parts reference categories/brands/countries by name via subqueries
-- rather than hardcoded IDs, so this file doesn't depend on
-- auto-increment starting at any particular value.
-- =====================================================================

USE vspms_db;

SET @adminId = (SELECT adminID FROM admin WHERE username = 'admin' LIMIT 1);

-- ---------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------

INSERT INTO category (parentCategoryID, categoryName, description) VALUES
(NULL, 'Engine Parts', 'Components for engine rebuilds and maintenance'),
(NULL, 'Brake System', 'Braking components for safe, reliable stopping'),
(NULL, 'Suspension & Steering', 'Ride comfort and handling components'),
(NULL, 'Electrical & Lighting', 'Batteries, lighting and electrical components'),
(NULL, 'Body & Exterior', 'Exterior trim, mirrors and body panels'),
(NULL, 'Transmission & Clutch', 'Manual and automatic transmission components'),
(NULL, 'Filters & Fluids', 'Filtration and lubrication products'),
(NULL, 'Wheels & Tyres', 'Tyres, alloy wheels and related accessories');

-- Each sub-category is its own INSERT ... SELECT ... FROM (derived table),
-- rather than a plain scalar subquery in a VALUES() list - MySQL refuses
-- "INSERT INTO category ... (SELECT ... FROM category ...)" in a single
-- statement ("You can't specify target table 'category' for update in
-- FROM clause"). Wrapping the lookup in its own derived table (the
-- `FROM (SELECT ...) AS p` part) sidesteps that restriction.
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Pistons & Rings', 'Pistons, piston rings and gudgeon pins' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Engine Parts') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Belts & Timing', 'Timing belts, chains and tensioners' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Engine Parts') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Brake Pads', 'Front and rear brake pad sets' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Brake System') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Brake Discs & Drums', 'Brake discs, rotors and drums' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Brake System') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Shock Absorbers', 'Front and rear shock absorbers and struts' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Suspension & Steering') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Control Arms', 'Suspension control arms and bushings' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Suspension & Steering') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Batteries', 'Starter batteries for petrol and diesel vehicles' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Electrical & Lighting') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Headlights & Bulbs', 'Headlamp units and replacement bulbs' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Electrical & Lighting') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Mirrors', 'Side mirrors and mirror glass' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Body & Exterior') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Bumpers', 'Front and rear bumper covers' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Body & Exterior') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Clutch Kits', 'Clutch plate, cover and release bearing kits' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Transmission & Clutch') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Oil Filters', 'Engine oil filters' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Filters & Fluids') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Air Filters', 'Engine air intake filters' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Filters & Fluids') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Tyres', 'Passenger and SUV tyres' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Wheels & Tyres') AS p;
INSERT INTO category (parentCategoryID, categoryName, description)
SELECT id, 'Alloy Wheels', 'Replacement alloy wheel rims' FROM (SELECT categoryID AS id FROM category WHERE categoryName = 'Wheels & Tyres') AS p;

-- ---------------------------------------------------------------------
-- Brands
-- ---------------------------------------------------------------------

INSERT INTO brand (brandName, isAuthorized) VALUES
('Toyota Genuine', 1),
('Bosch', 1),
('Denso', 1),
('NGK', 1),
('Bridgestone', 1),
('Michelin', 1),
('KYB', 1),
('Aisin', 1),
('Exedy', 0),
('Mobil 1', 1),
('Castrol', 0),
('Yokohama', 0);

-- ---------------------------------------------------------------------
-- Countries
-- ---------------------------------------------------------------------

INSERT INTO country (countryName, countryCode, importDutyRate) VALUES
('Japan', 'JPN', 15.00),
('Germany', 'DEU', 20.00),
('United States', 'USA', 25.00),
('China', 'CHN', 10.00),
('India', 'IND', 12.50),
('Thailand', 'THA', 8.00),
('South Korea', 'KOR', 15.00),
('Sri Lanka', 'LKA', 0.00),
('Indonesia', 'IDN', 8.00),
('Malaysia', 'MYS', 10.00);

-- ---------------------------------------------------------------------
-- Spare parts
-- ---------------------------------------------------------------------

INSERT INTO spare_part (categoryID, brandID, countryID, adminID, partName, partNumber, description, price, size, stockQty, minStockLevel, isActive) VALUES
((SELECT categoryID FROM category WHERE categoryName = 'Pistons & Rings'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Piston Ring Set - 2.0L Petrol', 'PRS-2000-STD', 'Complete piston ring set for 2.0L petrol engines, standard bore.', 12500.00, NULL, 25, 5, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Pistons & Rings'), (SELECT brandID FROM brand WHERE brandName = 'Aisin'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Piston Kit - 1.5L Diesel', 'PK-1500-D01', 'Forged piston kit with rings and pin for 1.5L diesel engines.', 18900.00, NULL, 10, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Belts & Timing'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Timing Belt Kit with Tensioner', 'TBK-4471-GE', 'OEM-spec timing belt, tensioner and idler pulley kit.', 15750.00, NULL, 18, 5, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Belts & Timing'), (SELECT brandID FROM brand WHERE brandName = 'Denso'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Timing Chain Set', 'TCS-9002-JP', 'Duplex timing chain set with guides and tensioner.', 22400.00, NULL, 0, 3, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Brake Pads'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Front Brake Pad Set - Sedan', 'BP-F220-SD', 'Ceramic front brake pad set for mid-size sedans.', 8500.00, NULL, 40, 10, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Brake Pads'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Rear Brake Pad Set - SUV', 'BP-R330-SU', 'Semi-metallic rear brake pad set for SUVs and crossovers.', 9200.00, NULL, 32, 8, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Brake Pads'), (SELECT brandID FROM brand WHERE brandName = 'Exedy'), (SELECT countryID FROM country WHERE countryName = 'China'), @adminId, 'Front Brake Pad Set - Hatchback', 'BP-F110-HB', 'Budget-friendly front brake pad set for compact hatchbacks.', 4200.00, NULL, 60, 15, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Brake Discs & Drums'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Front Brake Disc Pair - Ventilated', 'BD-V500-FR', 'Ventilated front brake disc pair, 280mm diameter.', 16800.00, NULL, 14, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Brake Discs & Drums'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Rear Brake Drum Pair', 'BD-D210-RR', 'Solid rear brake drum pair for compact sedans.', 11200.00, NULL, 0, 5, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Shock Absorbers'), (SELECT brandID FROM brand WHERE brandName = 'KYB'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Front Shock Absorber Pair', 'SA-F600-KY', 'Gas-charged front shock absorber pair for sedans.', 24500.00, NULL, 16, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Shock Absorbers'), (SELECT brandID FROM brand WHERE brandName = 'KYB'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Rear Shock Absorber Pair - SUV', 'SA-R620-KY', 'Heavy-duty rear shock absorber pair for SUVs.', 27800.00, NULL, 9, 3, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Control Arms'), (SELECT brandID FROM brand WHERE brandName = 'Aisin'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Front Lower Control Arm', 'CA-L310-AS', 'Front lower control arm with ball joint, left or right.', 13400.00, NULL, 20, 5, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Control Arms'), (SELECT brandID FROM brand WHERE brandName = 'Exedy'), (SELECT countryID FROM country WHERE countryName = 'China'), @adminId, 'Rear Control Arm Bushing Kit', 'CA-B420-EX', 'Polyurethane rear control arm bushing kit, set of 4.', 6100.00, NULL, 28, 6, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Batteries'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Maintenance-Free Battery 12V 45Ah', 'BAT-45AH-BO', '12V 45Ah maintenance-free starter battery, 2-year warranty.', 19500.00, NULL, 22, 6, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Batteries'), (SELECT brandID FROM brand WHERE brandName = 'Denso'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Maintenance-Free Battery 12V 65Ah', 'BAT-65AH-DE', '12V 65Ah maintenance-free battery for SUVs and pickups.', 26900.00, NULL, 12, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Headlights & Bulbs'), (SELECT brandID FROM brand WHERE brandName = 'Denso'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Halogen Headlight Bulb H4 (Pair)', 'HL-H4-DE02', 'Long-life halogen H4 headlight bulb pair, 12V 60/55W.', 2400.00, NULL, 80, 20, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Headlights & Bulbs'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Headlight Assembly - Left', 'HL-ASM-L01', 'OEM-fit headlight assembly, left side, with wiring harness.', 32500.00, NULL, 6, 3, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Mirrors'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Side Mirror Assembly - Right', 'MR-ASM-R01', 'Power-adjustable side mirror assembly with turn signal.', 14200.00, NULL, 10, 3, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Mirrors'), (SELECT brandID FROM brand WHERE brandName = 'Exedy'), (SELECT countryID FROM country WHERE countryName = 'China'), @adminId, 'Mirror Glass Replacement - Left', 'MR-GLS-L02', 'Heated mirror glass replacement, left side, universal fit.', 3200.00, NULL, 35, 8, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Bumpers'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Front Bumper Cover', 'BP-FCOV-01', 'Primed front bumper cover, ready for paint.', 28900.00, NULL, 5, 2, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Bumpers'), (SELECT brandID FROM brand WHERE brandName = 'Exedy'), (SELECT countryID FROM country WHERE countryName = 'China'), @adminId, 'Rear Bumper Cover', 'BP-RCOV-02', 'Primed rear bumper cover for compact hatchbacks.', 24700.00, NULL, 0, 2, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Clutch Kits'), (SELECT brandID FROM brand WHERE brandName = 'Exedy'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Clutch Kit - 1.6L Manual', 'CK-1600-EX', '3-piece clutch kit: cover, disc and release bearing.', 21500.00, NULL, 14, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Clutch Kits'), (SELECT brandID FROM brand WHERE brandName = 'Aisin'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Clutch Kit - 2.0L Diesel', 'CK-2000-AS', 'Heavy-duty clutch kit for 2.0L diesel pickups.', 29800.00, NULL, 7, 3, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Oil Filters'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Oil Filter - Standard Spin-On', 'OF-STD-BO1', 'Standard spin-on oil filter for most petrol engines.', 1450.00, NULL, 120, 30, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Oil Filters'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Oil Filter - OEM Cartridge', 'OF-CART-TY1', 'OEM cartridge-type oil filter with seal ring.', 1850.00, NULL, 95, 25, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Air Filters'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Air Filter - Panel Type', 'AF-PNL-BO2', 'Pleated panel air filter, high dust-holding capacity.', 2100.00, NULL, 70, 20, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Air Filters'), (SELECT brandID FROM brand WHERE brandName = 'Denso'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Air Filter - Cylindrical Type', 'AF-CYL-DE3', 'Cylindrical air filter for pickups and SUVs.', 2650.00, NULL, 45, 15, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Tyres'), (SELECT brandID FROM brand WHERE brandName = 'Bridgestone'), (SELECT countryID FROM country WHERE countryName = 'Thailand'), @adminId, 'Bridgestone Turanza Tyre', 'TY-TUR-205', 'Touring tyre for sedans, comfortable and quiet ride.', 21500.00, '205/55 R16', 24, 6, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Tyres'), (SELECT brandID FROM brand WHERE brandName = 'Michelin'), (SELECT countryID FROM country WHERE countryName = 'Thailand'), @adminId, 'Michelin Primacy Tyre', 'TY-PRI-195', 'Fuel-efficient tyre with long tread life.', 19800.00, '195/65 R15', 30, 8, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Tyres'), (SELECT brandID FROM brand WHERE brandName = 'Yokohama'), (SELECT countryID FROM country WHERE countryName = 'Indonesia'), @adminId, 'Yokohama BluEarth Tyre', 'TY-BLU-215', 'Eco tyre with reduced rolling resistance.', 23200.00, '215/60 R17', 18, 5, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Tyres'), (SELECT brandID FROM brand WHERE brandName = 'Bridgestone'), (SELECT countryID FROM country WHERE countryName = 'Thailand'), @adminId, 'Bridgestone Dueler SUV Tyre', 'TY-DUE-235', 'All-terrain tyre for SUVs and crossovers.', 31500.00, '235/65 R17', 12, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Tyres'), (SELECT brandID FROM brand WHERE brandName = 'Michelin'), (SELECT countryID FROM country WHERE countryName = 'Thailand'), @adminId, 'Michelin Energy Saver Tyre', 'TY-ENE-175', 'Compact car tyre optimised for fuel economy.', 16400.00, '175/65 R14', 0, 6, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Alloy Wheels'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Alloy Wheel Rim 16-inch', 'AW-16IN-TY', 'OEM-style 16-inch alloy wheel rim, 5-spoke design.', 34500.00, '16 inch', 8, 3, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Alloy Wheels'), (SELECT brandID FROM brand WHERE brandName = 'Exedy'), (SELECT countryID FROM country WHERE countryName = 'China'), @adminId, 'Alloy Wheel Rim 17-inch', 'AW-17IN-EX', 'Aftermarket 17-inch alloy wheel rim, multi-spoke design.', 29900.00, '17 inch', 10, 3, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Engine Parts'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Spark Plug Set (4pc)', 'SP-SET4-BO', 'Iridium spark plug set, 4 pieces, long service life.', 5600.00, NULL, 55, 15, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Engine Parts'), (SELECT brandID FROM brand WHERE brandName = 'NGK'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Ignition Coil', 'IC-STD-NG1', 'Direct ignition coil, OEM fitment for most Japanese sedans.', 7200.00, NULL, 33, 10, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Engine Parts'), (SELECT brandID FROM brand WHERE brandName = 'Denso'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Radiator Assembly', 'RAD-ASM-DE', 'Aluminium radiator assembly with plastic tanks.', 18700.00, NULL, 9, 3, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Engine Parts'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Fuel Injector', 'FI-STD-BO3', 'Direct-fit fuel injector for common rail diesel engines.', 13900.00, NULL, 20, 6, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Brake System'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Brake Master Cylinder', 'BMC-STD-BO', 'Brake master cylinder assembly with reservoir.', 12800.00, NULL, 11, 4, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Brake System'), (SELECT brandID FROM brand WHERE brandName = 'Toyota Genuine'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'ABS Wheel Speed Sensor', 'ABS-WSS-01', 'Front wheel speed sensor for ABS-equipped vehicles.', 6400.00, NULL, 26, 8, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Suspension & Steering'), (SELECT brandID FROM brand WHERE brandName = 'KYB'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Power Steering Pump', 'PSP-STD-KY', 'Hydraulic power steering pump, direct OEM replacement.', 17600.00, NULL, 8, 3, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Suspension & Steering'), (SELECT brandID FROM brand WHERE brandName = 'Aisin'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Tie Rod End Set', 'TRE-SET-AS', 'Inner and outer tie rod end set, left and right.', 8900.00, NULL, 24, 6, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Electrical & Lighting'), (SELECT brandID FROM brand WHERE brandName = 'Denso'), (SELECT countryID FROM country WHERE countryName = 'Japan'), @adminId, 'Alternator - 90A', 'ALT-90A-DE', 'Remanufactured 90A alternator, direct fitment.', 23800.00, NULL, 7, 3, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Electrical & Lighting'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Starter Motor', 'STM-STD-BO', 'Remanufactured starter motor for petrol engines.', 21300.00, NULL, 6, 3, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Filters & Fluids'), (SELECT brandID FROM brand WHERE brandName = 'Mobil 1'), (SELECT countryID FROM country WHERE countryName = 'United States'), @adminId, 'Full Synthetic Engine Oil 5W-30 (4L)', 'OIL-5W30-M1', 'Fully synthetic engine oil, 4-litre container.', 9800.00, NULL, 50, 12, 1),
((SELECT categoryID FROM category WHERE categoryName = 'Filters & Fluids'), (SELECT brandID FROM brand WHERE brandName = 'Castrol'), (SELECT countryID FROM country WHERE countryName = 'Malaysia'), @adminId, 'Semi-Synthetic Engine Oil 10W-40 (4L)', 'OIL-10W40-CS', 'Semi-synthetic engine oil, 4-litre container.', 6900.00, NULL, 40, 10, 1),

((SELECT categoryID FROM category WHERE categoryName = 'Wheels & Tyres'), (SELECT brandID FROM brand WHERE brandName = 'Bosch'), (SELECT countryID FROM country WHERE countryName = 'Germany'), @adminId, 'Wheel Alignment Sensor Kit', 'WAK-STD-BO', 'Diagnostic wheel alignment sensor kit for workshop use.', 45200.00, NULL, 3, 2, 1);
