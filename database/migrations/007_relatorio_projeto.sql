-- Migration 007: Relatorio de projeto + log de atividades
--
--

CREATE TABLE IF NOT EXISTS projeto_atividades (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id      INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED DEFAULT NULL COMMENT 'Quem gerou o evento. NULL = evento do sistema (ex: exclusao em cascata)',
    tipo            VARCHAR(40)  NOT NULL COMMENT 'Ver ProjetoAtividadeModel::TIPO_*',
    -- 500 (nao 255): nome de usuario (ate 120) + titulo de tarefa (ate 150)
    -- juntos numa frase podem passar de 255 com folga.
    descricao       VARCHAR(500) NOT NULL COMMENT 'Texto pronto, ja formatado, pra exibir direto na timeline',
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_atividade_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_atividade_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_atividade_projeto_data (projeto_id, criado_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projeto_relatorios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id      INT UNSIGNED NOT NULL,
    autor_id        INT UNSIGNED NOT NULL COMMENT 'Quem escreveu/editou por ultimo (sempre o dono -- RN do relatorio)',
    conteudo        MEDIUMTEXT   NOT NULL,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_relatorio_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_relatorio_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_relatorio_projeto (projeto_id) -- RN: um unico relatorio por projeto
) ENGINE=InnoDB;
