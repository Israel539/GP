-- ============================================================================
-- MIGRACAO 006: CORRIGE PRAZOS ZERADOS NAS TAREFAS DO KANBAN

UPDATE tarefas
SET data_limite = NULL
WHERE data_limite = '0000-00-00';
