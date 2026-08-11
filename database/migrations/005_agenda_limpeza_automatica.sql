-- ============================================================================
-- MIGRACAO 005: LIMPEZA AUTOMATICA DE COMPROMISSOS CONCLUIDOS (AGENDA)
-- Rode este arquivo uma unica vez.
-- ============================================================================

-- Preferencia por usuario: liga/desliga a exclusao automatica de
-- compromissos com status = 'concluido' que ja passaram de 30 dias desde a
-- ultima atualizacao. Desativado por padrao (0) -- ninguem perde dados sem
-- ativar explicitamente essa opcao na tela da Agenda.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS agenda_limpeza_automatica TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = exclui automaticamente (via cron) compromissos concluidos ha mais de 30 dias';
