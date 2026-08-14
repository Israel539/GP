-- Migration 011: usuario pode solicitar suporte (indicando onde)
--
CREATE TABLE IF NOT EXISTS solicitacoes_suporte (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id              INT UNSIGNED NOT NULL,
    tipo_recurso            ENUM('projeto', 'conta', 'cartao', 'compromisso', 'plano_compra') NOT NULL,
    recurso_id              INT UNSIGNED NOT NULL,
    mensagem                TEXT NOT NULL,
    status                  ENUM('pendente', 'atendida', 'cancelada') NOT NULL DEFAULT 'pendente',
    criado_em               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendido_em             DATETIME NULL DEFAULT NULL,
    atendido_por_admin_id   INT UNSIGNED NULL DEFAULT NULL,

    CONSTRAINT fk_solicitsuporte_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitsuporte_admin   FOREIGN KEY (atendido_por_admin_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_solicitsuporte_status (status, criado_em)
) ENGINE=InnoDB;
