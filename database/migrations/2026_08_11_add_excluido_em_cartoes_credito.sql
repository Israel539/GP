-- Migration: adicionar coluna `excluido_em` em cartoes_credito (soft-delete)
-- Data: 2026-08-11
-- Up: adiciona a coluna se ela nao existir
SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cartoes_credito'
    AND COLUMN_NAME = 'excluido_em'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE cartoes_credito ADD COLUMN excluido_em DATETIME DEFAULT NULL COMMENT "Soft-delete timestamp"',
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
--     AND TABLE_NAME = 'cartoes_credito'
--     AND COLUMN_NAME = 'excluido_em'
-- );
-- SET @sql = IF(@col_exists = 1,
--   'ALTER TABLE cartoes_credito DROP COLUMN excluido_em',
--   'SELECT 1'
-- );
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;
