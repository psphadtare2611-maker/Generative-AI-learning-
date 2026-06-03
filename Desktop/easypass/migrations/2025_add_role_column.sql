-- Migration: Add role column to users
-- Date: 2025-12-06
-- NOTE: Ran via PHP to handle "IF NOT EXISTS" logic if needed, but here is the raw intent.
ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user';
UPDATE users SET role = 'admin' WHERE id = 1;
