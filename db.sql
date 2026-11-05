-- Cars List Database Schema
-- Run this script to create the database and table

CREATE DATABASE IF NOT EXISTS cars_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cars_db;

CREATE TABLE IF NOT EXISTS cars (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    make       VARCHAR(100)   NOT NULL,
    model      VARCHAR(100)   NOT NULL,
    year       YEAR           NOT NULL,
    color      VARCHAR(50)    NOT NULL,
    price      DECIMAL(10, 2) NOT NULL,
    mileage    INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample data
INSERT INTO cars (make, model, year, color, price, mileage) VALUES
  ('Toyota',     'Corolla',   2020, 'White',  18500.00,  25000),
  ('Honda',      'Civic',     2021, 'Black',  22000.00,  15000),
  ('Ford',       'Mustang',   2019, 'Red',    35000.00,  40000),
  ('BMW',        '3 Series',  2022, 'Blue',   45000.00,   8000),
  ('Mercedes',   'C-Class',   2021, 'Silver', 52000.00,  12000);
