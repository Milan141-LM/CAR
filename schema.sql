-- Run this file once to set up the database.
-- Replace 'cars_db' with your actual database name if needed.

CREATE DATABASE IF NOT EXISTS cars_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cars_db;

CREATE TABLE IF NOT EXISTS cars (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    make  VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year  YEAR        NOT NULL,
    color VARCHAR(50)  NOT NULL,
    price DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until   DATETIME     NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample car data
INSERT INTO cars (make, model, year, color, price) VALUES
('Toyota',  'Corolla',  2020, 'White',  18000.00),
('Honda',   'Civic',    2021, 'Black',  20000.00),
('Ford',    'Mustang',  2019, 'Red',    35000.00),
('BMW',     '3 Series', 2022, 'Blue',   45000.00),
('Audi',    'A4',       2021, 'Silver', 42000.00);
