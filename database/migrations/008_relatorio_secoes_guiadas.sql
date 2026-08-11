-- Migration 008: relatorio de projeto em secoes guiadas

ALTER TABLE projeto_relatorios
    CHANGE COLUMN conteudo o_que_foi_feito MEDIUMTEXT NOT NULL,
    ADD COLUMN contexto MEDIUMTEXT NULL AFTER autor_id,
    ADD COLUMN decisoes MEDIUMTEXT NULL AFTER o_que_foi_feito,
    ADD COLUMN proximos_passos MEDIUMTEXT NULL AFTER decisoes;
