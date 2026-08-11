-- Migration: adicionar coluna `excluido_por_admin` em contatos
-- Data: 2026-08-11
-- Up: adiciona a coluna se ela nao existir
SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'contatos'
    AND COLUMN_NAME = 'excluido_por_admin'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE contatos ADD COLUMN excluido_por_admin TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Down: remove a coluna se existir
-- (usar manualmente se precisar reverter)
-- SET @col_exists = (
--   SELECT COUNT(*)
--   FROM information_schema.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME = 'contatos'
--     AND COLUMN_NAME = 'excluido_por_admin'
-- );
-- SET @sql = IF(@col_exists = 1,
--   'ALTER TABLE contatos DROP COLUMN excluido_por_admin',
--   'SELECT 1'
-- );
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;
