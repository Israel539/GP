-- ============================================================================
-- MIGRACAO 003: PARCELAMENTO EM TRANSACOES DE CREDITO
ALTER TABLE transacoes ADD COLUMN  parcela_atual TINYINT UNSIGNED DEFAULT NULL
    COMMENT 'Ex: 2 (a 2a parcela de um total de parcela_total) -- NULL quando a transacao nao e parcelada';
ALTER TABLE transacoes ADD COLUMN  parcela_total TINYINT UNSIGNED DEFAULT NULL
    COMMENT 'Quantidade total de parcelas do parcelamento -- NULL quando a transacao nao e parcelada';
ALTER TABLE transacoes ADD COLUMN  grupo_parcela_id CHAR(36) DEFAULT NULL
    COMMENT 'Mesmo valor em todas as parcelas de uma mesma compra parcelada';

ALTER TABLE transacoes ADD INDEX idx_transacao_grupo_parcela (grupo_parcela_id);