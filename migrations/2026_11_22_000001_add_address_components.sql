SET @add_street = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'street'),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN street VARCHAR(200) NULL DEFAULT NULL'
);
PREPARE add_street_stmt FROM @add_street;
EXECUTE add_street_stmt;
DEALLOCATE PREPARE add_street_stmt;

SET @add_barangay = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'barangay'),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN barangay VARCHAR(80) NULL DEFAULT NULL'
);
PREPARE add_barangay_stmt FROM @add_barangay;
EXECUTE add_barangay_stmt;
DEALLOCATE PREPARE add_barangay_stmt;

SET @add_city = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'city'),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN city VARCHAR(80) NULL DEFAULT NULL'
);
PREPARE add_city_stmt FROM @add_city;
EXECUTE add_city_stmt;
DEALLOCATE PREPARE add_city_stmt;

SET @add_province = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'province'),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN province VARCHAR(80) NULL DEFAULT NULL'
);
PREPARE add_province_stmt FROM @add_province;
EXECUTE add_province_stmt;
DEALLOCATE PREPARE add_province_stmt;

SET @add_postal_code = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'postal_code'),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN postal_code VARCHAR(20) NULL DEFAULT NULL'
);
PREPARE add_postal_code_stmt FROM @add_postal_code;
EXECUTE add_postal_code_stmt;
DEALLOCATE PREPARE add_postal_code_stmt;
