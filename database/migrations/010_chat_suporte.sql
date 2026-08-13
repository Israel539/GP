-- Migration 010: chat de suporte (ao vivo, durante o acesso auditado)
--

ALTER TABLE log_acesso_suporte
    ADD COLUMN encerrado_em DATETIME NULL DEFAULT NULL AFTER expira_em;

CREATE TABLE IF NOT EXISTS mensagens_suporte (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_acesso_id   INT UNSIGNED NOT NULL COMMENT 'Sessao de suporte (log_acesso_suporte.id) a que esse chat pertence',
    autor_id        INT UNSIGNED NOT NULL,
    mensagem        TEXT NOT NULL,
    enviado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_msgsuporte_log   FOREIGN KEY (log_acesso_id) REFERENCES log_acesso_suporte(id) ON DELETE CASCADE,
    CONSTRAINT fk_msgsuporte_autor FOREIGN KEY (autor_id)      REFERENCES usuarios(id)            ON DELETE CASCADE,
    INDEX idx_msgsuporte_log (log_acesso_id, id)
) ENGINE=InnoDB;
