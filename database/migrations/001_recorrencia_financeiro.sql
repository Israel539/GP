-- ============================================================================
-- MIGRACAO 001: CONECTA TRANSACOES RECORRENTES COM O FINANCEIRO
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'transacoes_recorrentes'
  AND COLUMN_NAME = 'cartao_id';


ALTER TABLE transacoes_recorrentes 
ADD COLUMN cartao_id INT UNSIGNED DEFAULT NULL
COMMENT 'Obrigatorio quando modalidade = credito (RN09)';