-- ============================================================================
-- PROJETO GP (Personal Hub) - Schema de Banco de Dados
-- Banco: gpdb | Engine: InnoDB (necessário para Foreign Keys) | Charset: utf8mb4
-- ============================================================================
-- Este schema cobre os 3 pilares do sistema:
--   1. Agenda Pessoal
--   2. Gestão de Projetos (com colaboração entre usuários + chat)
--   3. Gestão Financeira (com integração Open Finance / Pluggy)
--
-- Cada tabela referencia, em comentário, a(s) Regra(s) de Negócio (RN) do
-- documento de especificação que ela ajuda a sustentar.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS gpdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gpdb;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- MÓDULO 0: USUÁRIOS (base de autenticação, compartilhada por todos os módulos)
-- ============================================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(120)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    senha           VARCHAR(255)    NOT NULL COMMENT 'Hash gerado por password_hash(), nunca texto puro',
    cpf             VARCHAR(14)     DEFAULT NULL,
    data_nascimento DATE            DEFAULT NULL,
    telefone_whats  VARCHAR(20)     DEFAULT NULL COMMENT 'Número no formato internacional p/ WhatsApp Cloud API',
    foto            VARCHAR(255)    DEFAULT NULL,
    nivel           TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1=Admin, 2=Usuario comum',
    statusRegistro  TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Ativo, 2=Inativo/Banido',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

-- Cria o primeiro Admin do sistema (equivalente ao criaSuperUser() do Login.php,
-- mas já com hash de senha de verdade em vez de senha em texto puro).
-- Rode manualmente uma vez, com uma senha forte, e apague este INSERT depois:
-- INSERT INTO usuarios (nome, email, senha, nivel, statusRegistro)
-- VALUES ('Admin', 'admin@seudominio.com', '<hash gerado por password_hash>', 1, 1);

-- ============================================================================
-- MÓDULO 1: AGENDA PESSOAL
-- ============================================================================

CREATE TABLE IF NOT EXISTS compromissos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id              INT UNSIGNED NOT NULL,
    titulo                  VARCHAR(150) NOT NULL,
    descricao               TEXT         DEFAULT NULL,
    tipo                    ENUM('reuniao_presencial','tarefa_pessoal','lembrete','outro') NOT NULL DEFAULT 'outro',
    -- 'reuniao_presencial' é o tipo verificado pela RN01 (unicidade de horário)
    data_inicio             DATETIME     NOT NULL,
    data_fim                DATETIME     NOT NULL,
    local                   VARCHAR(200) DEFAULT NULL,
    status                  ENUM('pendente','concluido','cancelado') NOT NULL DEFAULT 'pendente',
    -- RN03: notificação só dispara p/ status = 'pendente'
    notificar_whatsapp      TINYINT(1)   NOT NULL DEFAULT 0,
    notificar_email         TINYINT(1)   NOT NULL DEFAULT 0,
    notificado_whatsapp_em  DATETIME     DEFAULT NULL COMMENT 'RN03: flag de envio p/ evitar spam',
    notificado_email_em     DATETIME     DEFAULT NULL COMMENT 'RN03: flag de envio p/ evitar spam',
    criado_em               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_compromissos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    -- RN02 (data_fim > data_inicio) fica validada na camada de Model (fat model),
    -- pois MySQL só suporta CHECK envolvendo colunas a partir da 8.0.16 e nem
    -- todo hosting compartilhado garante isso — mais seguro validar em PHP.
    INDEX idx_compromissos_usuario_data (usuario_id, data_inicio)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 2: GESTÃO DE PROJETOS (com colaboração + chat)
-- ============================================================================

CREATE TABLE IF NOT EXISTS projetos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dono_id         INT UNSIGNED NOT NULL COMMENT 'Usuário que criou o projeto',
    nome            VARCHAR(150) NOT NULL,
    descricao       TEXT         DEFAULT NULL,
    status          ENUM('planejamento','em_andamento','concluido','cancelado') NOT NULL DEFAULT 'planejamento',
    -- RN05: só pode ir p/ 'concluido' se não houver tarefa em 'a_fazer'/'em_andamento'
    data_inicio     DATE         DEFAULT NULL,
    data_entrega    DATE         DEFAULT NULL,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_projetos_dono FOREIGN KEY (dono_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela pivô: quem participa de cada projeto (é isso que viabiliza "eu e meu
-- amigo trabalhando no mesmo projeto")
CREATE TABLE IF NOT EXISTS projeto_usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id      INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    papel           ENUM('dono','colaborador') NOT NULL DEFAULT 'colaborador',
    entrou_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pu_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pu_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_projeto_usuario (projeto_id, usuario_id) -- impede vincular a mesma pessoa 2x
) ENGINE=InnoDB;

-- Convites pendentes (necessário p/ "meu amigo vai criar outra conta e entrar
-- no projeto" -- alguém precisa convidar por e-mail/username antes de virar
-- registro em projeto_usuarios)
CREATE TABLE IF NOT EXISTS projeto_convites (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id      INT UNSIGNED NOT NULL,
    convidado_por   INT UNSIGNED NOT NULL,
    email_convidado VARCHAR(150) NOT NULL,
    status          ENUM('pendente','aceito','recusado','expirado') NOT NULL DEFAULT 'pendente',
    token           VARCHAR(100) NOT NULL,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_convite_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_convite_usuario FOREIGN KEY (convidado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_convite_token (token)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tarefas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id      INT UNSIGNED NOT NULL,
    responsavel_id  INT UNSIGNED DEFAULT NULL COMMENT 'Usuário responsável (deve pertencer ao projeto)',
    titulo          VARCHAR(150) NOT NULL,
    descricao       TEXT         DEFAULT NULL,
    status          ENUM('a_fazer','em_andamento','concluido') NOT NULL DEFAULT 'a_fazer',
    -- RN04: transição de status só pode seguir a sequência válida -- isso é
    -- validado no Model (TarefaModel::moverStatus()), não é feito só via ENUM.
    data_limite     DATE         DEFAULT NULL,
    concluida_em    DATETIME     DEFAULT NULL,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tarefas_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_tarefas_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tarefas_projeto_status (projeto_id, status)
    -- RN06 (marcar "Atrasado") é calculado dinamicamente: data_limite < HOJE
    -- E status != 'concluido'. Não precisa de coluna própria -- evita ficar
    -- desatualizado. Se quiser performance em listagens grandes, dá pra criar
    -- uma coluna virtual gerada (GENERATED ALWAYS AS) depois.
) ENGINE=InnoDB;

-- Chat interno do projeto (o campo de mensagem que você pediu)
CREATE TABLE IF NOT EXISTS mensagens_projeto (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id      INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    mensagem        TEXT         NOT NULL,
    enviado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_msg_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_msg_projeto_data (projeto_id, enviado_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contatos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED DEFAULT NULL,
    nome            VARCHAR(120)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    assunto         VARCHAR(150)    NOT NULL,
    mensagem        TEXT            NOT NULL,
    resposta        TEXT            DEFAULT NULL,
    status          ENUM('pendente','respondido') NOT NULL DEFAULT 'pendente',
    respondido_por  INT UNSIGNED    DEFAULT NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respondido_em   DATETIME        DEFAULT NULL,

    CONSTRAINT fk_contato_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_contato_admin FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_contatos_status (status),
    INDEX idx_contatos_usuario (usuario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS termos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo            VARCHAR(50)    NOT NULL COMMENT 'termos_uso ou politica_privacidade',
    titulo          VARCHAR(150)   NOT NULL,
    conteudo        TEXT           NOT NULL,
    versao          VARCHAR(50)    NOT NULL,
    ativo           TINYINT(1)     NOT NULL DEFAULT 1,
    criado_em       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_termos_tipo_versao (tipo, versao),
    INDEX idx_termos_tipo_ativo (tipo, ativo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario_aceite_termos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED    NOT NULL,
    termo_id        INT UNSIGNED    NOT NULL,
    ip              VARCHAR(45)    DEFAULT NULL,
    user_agent      VARCHAR(255)   DEFAULT NULL,
    aceito_em       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_uat_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_uat_termo   FOREIGN KEY (termo_id)   REFERENCES termos(id)   ON DELETE CASCADE,
    UNIQUE KEY uq_uat_usuario_termo (usuario_id, termo_id),
    INDEX idx_uat_usuario (usuario_id)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 3: GESTÃO FINANCEIRA
-- ============================================================================

CREATE TABLE IF NOT EXISTS contas (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT UNSIGNED NOT NULL,
    nome                VARCHAR(100) NOT NULL COMMENT 'Ex: "Nubank", "Carteira", "Inter"',
    tipo                ENUM('corrente','poupanca','carteira','investimento') NOT NULL DEFAULT 'corrente',
    saldo_inicial       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    instituicao         VARCHAR(100) DEFAULT NULL,
    pluggy_account_id   VARCHAR(100) DEFAULT NULL COMMENT 'ID da conta na API Pluggy, p/ sincronização (RN10)',
    criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_contas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cartoes_credito (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conta_pagadora_id   INT UNSIGNED NOT NULL COMMENT 'Conta corrente que paga a fatura deste cartão',
    nome                VARCHAR(100) NOT NULL,
    limite              DECIMAL(14,2) DEFAULT NULL,
    dia_fechamento      TINYINT UNSIGNED NOT NULL COMMENT 'Dia do mês em que a fatura fecha',
    dia_vencimento      TINYINT UNSIGNED NOT NULL COMMENT 'Dia do mês em que a fatura vence',

    CONSTRAINT fk_cartao_conta FOREIGN KEY (conta_pagadora_id) REFERENCES contas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faturas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cartao_id       INT UNSIGNED NOT NULL,
    mes_referencia  DATE         NOT NULL COMMENT 'Sempre gravar como dia 01 do mês (ex: 2026-08-01)',
    valor_total     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    -- RN09: valor_total é a soma das transações de crédito vinculadas a esta fatura,
    -- e só impacta o saldo da conta pagadora na data_vencimento
    status          ENUM('aberta','fechada','paga') NOT NULL DEFAULT 'aberta',
    data_vencimento DATE         NOT NULL,
    data_pagamento  DATE         DEFAULT NULL,

    CONSTRAINT fk_fatura_cartao FOREIGN KEY (cartao_id) REFERENCES cartoes_credito(id) ON DELETE CASCADE,
    UNIQUE KEY uq_fatura_cartao_mes (cartao_id, mes_referencia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED DEFAULT NULL COMMENT 'NULL = categoria padrão do sistema, visível a todos',
    nome            VARCHAR(80)  NOT NULL,
    tipo            ENUM('receita','despesa') NOT NULL,
    icone           VARCHAR(50)  DEFAULT NULL,
    cor             VARCHAR(7)   DEFAULT NULL COMMENT 'Hex, ex: #FF5733',

    CONSTRAINT fk_categoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tags (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    nome            VARCHAR(50)  NOT NULL,

    CONSTRAINT fk_tags_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tag_usuario_nome (usuario_id, nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transacoes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conta_id            INT UNSIGNED NOT NULL,
    categoria_id        INT UNSIGNED DEFAULT NULL,
    fatura_id           INT UNSIGNED DEFAULT NULL COMMENT 'Preenchido só quando modalidade = credito (RN09)',
    descricao           VARCHAR(200) NOT NULL,
    valor               DECIMAL(14,2) NOT NULL COMMENT 'Sempre positivo; o "tipo" define receita/despesa',
    tipo                ENUM('receita','despesa') NOT NULL,
    modalidade          ENUM('pix','debito','credito','dinheiro','outro') NOT NULL DEFAULT 'outro',
    -- RN09: pix/debito -> impacta saldo imediatamente na data_fato_gerador
    --       credito    -> acumula na fatura, só impacta saldo na data_vencimento
    data_fato_gerador   DATE         NOT NULL COMMENT 'Data em que a transação de fato ocorreu',
    data_competencia    DATE         NOT NULL COMMENT 'Data contábil de referência (pode ser igual à anterior)',
    status              ENUM('confirmada','pendente') NOT NULL DEFAULT 'confirmada',
    -- RN08: saldo = SUM(receita confirmada) - SUM(despesa efetivada) -- calculado
    -- em tempo real via query/VIEW, não fica armazenado numa coluna "saldo".

    origem              ENUM('manual','api_openfinance') NOT NULL DEFAULT 'manual',
    id_externo          VARCHAR(150) DEFAULT NULL COMMENT 'ID retornado pela API Pluggy -- RN10',
    instituicao_externa VARCHAR(100) DEFAULT NULL,

    criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_transacao_conta      FOREIGN KEY (conta_id)     REFERENCES contas(id)      ON DELETE CASCADE,
    CONSTRAINT fk_transacao_categoria  FOREIGN KEY (categoria_id) REFERENCES categorias(id)  ON DELETE SET NULL,
    CONSTRAINT fk_transacao_fatura     FOREIGN KEY (fatura_id)    REFERENCES faturas(id)     ON DELETE SET NULL,

    -- RN10: garante, no próprio banco, que o mesmo id_externo nunca é duplicado
    -- (chave única condicional -- MySQL permite múltiplos NULL em UNIQUE, então
    -- transações manuais, sem id_externo, não são afetadas por essa restrição)
    UNIQUE KEY uq_transacao_id_externo (id_externo),
    INDEX idx_transacao_conta_data (conta_id, data_fato_gerador)
) ENGINE=InnoDB;

-- Pivô N:N transação <-> tag (RN07 libera edição livre de tags mesmo em
-- transações importadas via API)
CREATE TABLE IF NOT EXISTS transacao_tags (
    transacao_id    INT UNSIGNED NOT NULL,
    tag_id          INT UNSIGNED NOT NULL,
    PRIMARY KEY (transacao_id, tag_id),
    CONSTRAINT fk_tt_transacao FOREIGN KEY (transacao_id) REFERENCES transacoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_tt_tag       FOREIGN KEY (tag_id)       REFERENCES tags(id)       ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VIEW auxiliar para RN08 (saldo consolidado em tempo real por conta)
-- ============================================================================
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
LEFT JOIN transacoes t ON t.conta_id = c.id
GROUP BY c.id, c.usuario_id, c.nome, c.saldo_inicial;
