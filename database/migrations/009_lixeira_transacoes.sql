-- Migration 009: lixeira de transacoes (excluir com restauracao em ate 1 dia)
--
ALTER TABLE transacoes
    ADD COLUMN excluido_em DATETIME NULL DEFAULT NULL AFTER status,
    ADD INDEX idx_transacoes_excluido_em (excluido_em);

CREATE OR REPLACE VIEW vw_saldo_contas AS
SELECT
    c.id AS conta_id,
    c.usuario_id,
    c.nome,
    c.saldo_inicial
        + COALESCE(SUM(CASE
            WHEN t.tipo = 'receita' AND t.status = 'confirmada' AND t.modalidade != 'credito' THEN t.valor
            WHEN t.tipo = 'despesa' AND t.status = 'confirmada' AND t.modalidade != 'credito' THEN -t.valor
            ELSE 0
          END), 0) AS saldo_atual
FROM contas c
-- excluido_em IS NULL fica na condicao do JOIN (nao de um WHERE) para nao
-- perder contas sem nenhuma transacao (LEFT JOIN continua trazendo a conta
-- com saldo = saldo_inicial nesse caso).
LEFT JOIN transacoes t ON t.conta_id = c.id AND t.excluido_em IS NULL
GROUP BY c.id, c.usuario_id, c.nome, c.saldo_inicial;
