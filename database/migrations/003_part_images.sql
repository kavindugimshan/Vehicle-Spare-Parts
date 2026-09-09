-- =====================================================================
-- database/migrations/003_part_images.sql
--
-- Final phase (docs/PROJECT_BRIEF.md, Section 12.3): assigns every
-- spare_part row a category-based image, so partImage() renders a real
-- <img> instead of the initials placeholder. Per Section 3, Rule 3,
-- schema.sql itself is never edited - this is a new numbered migration
-- instead.
--
-- Each part gets its TOP-LEVEL category's icon (resolved via the
-- self-join below, so a part filed under a sub-category like "Tyres"
-- still correctly picks up "Wheels & Tyres"'s icon), from the 8 SVGs in
-- assets/images/parts/. Safe to re-run.
-- =====================================================================

USE vspms_db;

UPDATE spare_part sp
JOIN category c ON c.categoryID = sp.categoryID
JOIN category top ON top.categoryID = COALESCE(c.parentCategoryID, c.categoryID)
SET sp.imageURL = CASE top.categoryName
    WHEN 'Engine Parts'            THEN 'assets/images/parts/engine-parts.svg'
    WHEN 'Brake System'            THEN 'assets/images/parts/brake-system.svg'
    WHEN 'Suspension & Steering'   THEN 'assets/images/parts/suspension-steering.svg'
    WHEN 'Electrical & Lighting'   THEN 'assets/images/parts/electrical-lighting.svg'
    WHEN 'Body & Exterior'         THEN 'assets/images/parts/body-exterior.svg'
    WHEN 'Transmission & Clutch'   THEN 'assets/images/parts/transmission-clutch.svg'
    WHEN 'Filters & Fluids'        THEN 'assets/images/parts/filters-fluids.svg'
    WHEN 'Wheels & Tyres'          THEN 'assets/images/parts/wheels-tyres.svg'
    ELSE sp.imageURL
END
WHERE sp.imageURL IS NULL;
