-- ============================================================================
-- MIGRACAO 004: COMPROMISSOS RECORRENTES NA AGENDA
-- Rode este arquivo uma unica vez.
-- ============================================================================

-- Vincula um compromisso gerado automaticamente ao "molde" que o originou --
-- mesmo espirito do grupo_parcela_id em transacoes: nao muda nada na regra
-- de negocio do compromisso em si (RN01/RN02/RN03 continuam valendo
-- normalmente pra cada ocorrencia gerada), so serve pra identificar a
-- origem e mostrar um badge na tela.
ALTER TABLE compromissos ADD COLUMN IF NOT EXISTS recorrencia_id INT UNSIGNED DEFAULT NULL
    COMMENT 'Preenchido quando o compromisso foi gerado automaticamente por uma recorrencia';

CREATE TABLE IF NOT EXISTS compromissos_recorrentes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT UNSIGNED NOT NULL,
    titulo              VARCHAR(150) NOT NULL,
    descricao           TEXT         DEFAULT NULL,
    tipo                ENUM('reuniao_presencial','tarefa_pessoal','lembrete','outro') NOT NULL DEFAULT 'outro',
    local               VARCHAR(200) DEFAULT NULL,
    dia_semana          TINYINT UNSIGNED NOT NULL COMMENT '0=domingo, 1=segunda, ..., 6=sabado',
    hora_inicio         TIME         NOT NULL,
    hora_fim            TIME         NOT NULL,
    data_inicio         DATE         NOT NULL COMMENT 'A partir de quando a recorrencia vale',
    data_fim            DATE         DEFAULT NULL COMMENT 'Opcional -- ex: fim do semestre',
    notificar_email     TINYINT(1)   NOT NULL DEFAULT 1,
    ativa               TINYINT(1)   NOT NULL DEFAULT 1,
    ultima_data_gerada  DATE         DEFAULT NULL COMMENT 'Ate qual data ja foram criados compromissos de verdade',
    criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_compromisso_recorrente_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_compromisso_recorrente_usuario (usuario_id)
) ENGINE=InnoDB;

-- NAO idempotente (MySQL puro nao aceita "ADD CONSTRAINT IF NOT EXISTS") --
-- se rodar o arquivo de novo, comente esta linha.
ALTER TABLE compromissos ADD CONSTRAINT fk_compromissos_recorrencia
    FOREIGN KEY (recorrencia_id) REFERENCES compromissos_recorrentes(id) ON DELETE SET NULL;
