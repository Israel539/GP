-- ============================================================================
-- PROJETO GP (Personal Hub) - Schema de Banco de Dados
-- Banco: gpdb | Engine: InnoDB (necessário para Foreign Keys) | Charset: utf8mb4
-- ============================================================================
-- Este schema cobre os 4 pilares do sistema:
-- 1. Agenda Pessoal
-- 2. Gestão de Projetos (com colaboração entre usuários, chat e relatório
-- exportável em PDF/DOCX)
-- 3. Gestão Financeira (com integração Open Finance / Pluggy, extrato
-- mensal e lixeira de transações com restauração em até 1 dia)
-- 4. Suporte (acesso pontual e auditado do Admin a dados de outro
-- usuário, solicitável pelo próprio usuário, com chat ao vivo
-- durante o atendimento)
--
-- Cada tabela referencia, em comentário, a(s) Regra(s) de Negócio (RN) do
-- documento de especificação que ela ajuda a sustentar.
--
-- Este arquivo já reflete o estado final do banco (schema completo,
-- migrações 001 a 014 já incorporadas). Uma instalação nova roda só este
-- arquivo -- não precisa rodar nada de database/migrations/ depois.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS gpdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gpdb;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- MÓDULO 0: USUÁRIOS (base de autenticação, compartilhada por todos os módulos)
-- ============================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha VARCHAR(255) NOT NULL COMMENT 'Hash gerado por password_hash(), nunca texto puro',
    cpf VARCHAR(14) DEFAULT NULL,
    data_nascimento DATE DEFAULT NULL,
    telefone_whats VARCHAR(20) DEFAULT NULL COMMENT 'Número no formato internacional p/ WhatsApp Cloud API',
    foto VARCHAR(255) DEFAULT NULL,
    nivel TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1=Admin, 2=Usuario comum',
    statusRegistro TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Ativo, 2=Inativo/Banido',
    reset_token VARCHAR(255) DEFAULT NULL COMMENT 'Token de "esqueci minha senha", uso unico',
    reset_token_expira_em DATETIME DEFAULT NULL,
    tentativas_login TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Zera a cada login bem-sucedido',
    bloqueado_ate DATETIME DEFAULT NULL COMMENT 'Rate limiting: login recusado enquanto NOW() < bloqueado_ate',
    agenda_limpeza_automatica TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = exclui automaticamente (via cron) compromissos concluidos ha mais de 30 dias',
    saldo_dinheiro DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'OBSOLETO desde a migracao 014 -- "Dinheiro Fisico" agora e uma conta comum (ver contas.eh_conta_dinheiro). Coluna mantida sem uso, so por seguranca.',
    exibir_saldo_dinheiro TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'OBSOLETO desde a migracao 014, mesmo motivo da coluna acima.',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT DEFAULT NULL,
    tipo ENUM('reuniao_presencial','tarefa_pessoal','lembrete','outro') NOT NULL DEFAULT 'outro',
    -- 'reuniao_presencial' é o tipo verificado pela RN01 (unicidade de horário)
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    local VARCHAR(200) DEFAULT NULL,
    status ENUM('pendente','concluido','cancelado') NOT NULL DEFAULT 'pendente',
    -- RN03: notificação só dispara p/ status = 'pendente'
    notificar_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    notificar_email TINYINT(1) NOT NULL DEFAULT 0,
    notificado_whatsapp_em DATETIME DEFAULT NULL COMMENT 'RN03: flag de envio p/ evitar spam',
    notificado_email_em DATETIME DEFAULT NULL COMMENT 'RN03: flag de envio p/ evitar spam',
    recorrencia_id INT UNSIGNED DEFAULT NULL COMMENT 'Preenchido quando o compromisso foi gerado automaticamente por uma recorrencia (compromissos_recorrentes, abaixo)',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_compromissos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    -- RN02 (data_fim > data_inicio) fica validada na camada de Model (fat model),
    -- pois MySQL só suporta CHECK envolvendo colunas a partir da 8.0.16 e nem
    -- todo hosting compartilhado garante isso — mais seguro validar em PHP.
    INDEX idx_compromissos_usuario_data (usuario_id, data_inicio)
) ENGINE=InnoDB;

-- Molde de compromisso recorrente (ex: "reunião toda segunda às 9h") -- um
-- cron confere diariamente quais precisam gerar um compromisso de verdade
-- na tabela acima (vinculado de volta via compromissos.recorrencia_id).
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

-- FK de compromissos.recorrencia_id só entra AQUI, depois de
-- compromissos_recorrentes já existir (MySQL não aceita referenciar uma
-- tabela que ainda não foi criada dentro do próprio CREATE TABLE).
ALTER TABLE compromissos
    ADD CONSTRAINT fk_compromissos_recorrencia
    FOREIGN KEY (recorrencia_id) REFERENCES compromissos_recorrentes(id) ON DELETE SET NULL;

-- ============================================================================
-- MÓDULO 2: GESTÃO DE PROJETOS (com colaboração + chat)
-- ============================================================================
CREATE TABLE IF NOT EXISTS projetos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dono_id INT UNSIGNED NOT NULL COMMENT 'Usuário que criou o projeto',
    nome VARCHAR(150) NOT NULL,
    descricao TEXT DEFAULT NULL,
    status ENUM('planejamento','em_andamento','concluido','cancelado') NOT NULL DEFAULT 'planejamento',
    -- RN05: só pode ir p/ 'concluido' se não houver tarefa em 'a_fazer'/'em_andamento'
    data_inicio DATE DEFAULT NULL,
    data_entrega DATE DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projetos_dono FOREIGN KEY (dono_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela pivô: quem participa de cada projeto (é isso que viabiliza "eu e meu
-- amigo trabalhando no mesmo projeto")
CREATE TABLE IF NOT EXISTS projeto_usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    papel ENUM('dono','colaborador') NOT NULL DEFAULT 'colaborador',
    entrou_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pu_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pu_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_projeto_usuario (projeto_id, usuario_id) -- impede vincular a mesma pessoa 2x
) ENGINE=InnoDB;

-- Convites pendentes (necessário p/ "meu amigo vai criar outra conta e entrar
-- no projeto" -- alguém precisa convidar por e-mail/username antes de virar
-- registro em projeto_usuarios)
CREATE TABLE IF NOT EXISTS projeto_convites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    convidado_por INT UNSIGNED NOT NULL,
    email_convidado VARCHAR(150) NOT NULL,
    status ENUM('pendente','aceito','recusado','expirado') NOT NULL DEFAULT 'pendente',
    token VARCHAR(100) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_convite_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_convite_usuario FOREIGN KEY (convidado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_convite_token (token)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tarefas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    responsavel_id INT UNSIGNED DEFAULT NULL COMMENT 'Usuário responsável (deve pertencer ao projeto)',
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT DEFAULT NULL,
    status ENUM('a_fazer','em_andamento','concluido') NOT NULL DEFAULT 'a_fazer',
    -- RN04: transição de status só pode seguir a sequência válida -- isso é
    -- validado no Model (TarefaModel::moverStatus()), não é feito só via ENUM.
    data_limite DATE DEFAULT NULL,
    concluida_em DATETIME DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    mensagem TEXT NOT NULL,
    enviado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_msg_projeto_data (projeto_id, enviado_em)
) ENGINE=InnoDB;

-- Log cronologico de eventos do projeto (quem fez o que e quando). So
-- alimentada pelos Controllers a partir da existencia desta tabela -- e
-- isso que sustenta a timeline do relatorio de projeto (abaixo) e serve
-- como trilha de auditoria do projeto em geral.
CREATE TABLE IF NOT EXISTS projeto_atividades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED DEFAULT NULL COMMENT 'Quem gerou o evento. NULL = evento do sistema',
    tipo VARCHAR(40) NOT NULL COMMENT 'Ver ProjetoAtividadeModel::TIPO_*',
    descricao VARCHAR(500) NOT NULL COMMENT 'Texto pronto, ja formatado, pra exibir direto na timeline',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_atividade_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_atividade_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_atividade_projeto_data (projeto_id, criado_em)
) ENGINE=InnoDB;

-- Relatorio do projeto (um por projeto, editavel -- nao versionado),
-- escrito pelo dono em 4 secoes guiadas. So 'o_que_foi_feito' e
-- obrigatorio; as demais ficam a criterio de quem escreve. E exportado em
-- PDF/DOCX junto com a lista de participantes e a timeline de
-- projeto_atividades + mensagens_projeto.
CREATE TABLE IF NOT EXISTS projeto_relatorios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    autor_id INT UNSIGNED NOT NULL COMMENT 'Quem escreveu/editou por ultimo (sempre o dono do projeto)',
    contexto MEDIUMTEXT DEFAULT NULL,
    o_que_foi_feito MEDIUMTEXT NOT NULL,
    decisoes MEDIUMTEXT DEFAULT NULL,
    proximos_passos MEDIUMTEXT DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_relatorio_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    CONSTRAINT fk_relatorio_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_relatorio_projeto (projeto_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contatos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED DEFAULT NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    assunto VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    resposta TEXT DEFAULT NULL,
    status ENUM('pendente','respondido','excluido') NOT NULL DEFAULT 'pendente',
    respondido_por INT UNSIGNED DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respondido_em DATETIME DEFAULT NULL,
    excluido_em DATETIME DEFAULT NULL,
    excluido_por_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Distingue exclusao pelo admin de exclusao pelo proprio usuario',
    CONSTRAINT fk_contato_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_contato_admin FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_contatos_status (status),
    INDEX idx_contatos_usuario (usuario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS termos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL COMMENT 'termos_uso ou politica_privacidade',
    titulo VARCHAR(150) NOT NULL,
    conteudo TEXT NOT NULL,
    versao VARCHAR(50) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_termos_tipo_versao (tipo, versao),
    INDEX idx_termos_tipo_ativo (tipo, ativo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario_aceite_termos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    termo_id INT UNSIGNED NOT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    aceito_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_uat_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_uat_termo FOREIGN KEY (termo_id) REFERENCES termos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_uat_usuario_termo (usuario_id, termo_id),
    INDEX idx_uat_usuario (usuario_id)
) ENGINE=InnoDB;

-- ============================================================================
-- MÓDULO 3: GESTÃO FINANCEIRA
-- ============================================================================
CREATE TABLE IF NOT EXISTS contas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL COMMENT 'Ex: "Nubank", "Carteira", "Inter"',
    tipo ENUM('corrente','poupanca','carteira','investimento') NOT NULL DEFAULT 'corrente',
    eh_conta_dinheiro TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Marca qual conta do usuario recebe transacoes com modalidade=dinheiro automaticamente (RN10). So uma por usuario -- garantido pela aplicacao (ContaModel::definirContaDinheiro), nao da pra fazer isso de forma portavel so com constraint de banco.',
    saldo_inicial DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    instituicao VARCHAR(100) DEFAULT NULL,
    pluggy_account_id VARCHAR(100) DEFAULT NULL COMMENT 'ID da conta na API Pluggy, p/ sincronização (RN10)',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS planos_compra (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT DEFAULT NULL,
    imagem_url VARCHAR(255) DEFAULT NULL,
    produto_url VARCHAR(255) DEFAULT NULL,
    valor_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    parcelas_previstas TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('planejamento','em_andamento','concluido','cancelado','excluido') NOT NULL DEFAULT 'planejamento',
    data_prevista_compra DATE DEFAULT NULL,
    data_conclusao DATE DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    excluido_em DATETIME DEFAULT NULL,
    CONSTRAINT fk_plano_compra_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_plano_compra_parent FOREIGN KEY (parent_id) REFERENCES planos_compra(id) ON DELETE CASCADE,
    INDEX idx_planos_usuario_status (usuario_id, status),
    INDEX idx_planos_parent (parent_id)
) ENGINE=InnoDB;

-- Cada linha e um deposito/parcela de verdade guardado rumo ao plano (ex:
-- "guardei R$500 dia 05/07 pra geladeira"). valor_total do plano e a META;
-- a soma destas linhas e o QUANTO JA FOI GUARDADO ate agora.
CREATE TABLE IF NOT EXISTS plano_compra_parcelas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano_compra_id INT UNSIGNED NOT NULL,
    valor DECIMAL(14,2) NOT NULL,
    data_pagamento DATE NOT NULL,
    observacao VARCHAR(255) DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_parcela_plano_compra FOREIGN KEY (plano_compra_id) REFERENCES planos_compra(id) ON DELETE CASCADE,
    INDEX idx_parcela_plano (plano_compra_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cartoes_credito (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conta_pagadora_id INT UNSIGNED NOT NULL COMMENT 'Conta corrente que paga a fatura deste cartão',
    nome VARCHAR(100) NOT NULL,
    limite DECIMAL(14,2) DEFAULT NULL,
    dia_fechamento TINYINT UNSIGNED NOT NULL COMMENT 'Dia do mês em que a fatura fecha',
    dia_vencimento TINYINT UNSIGNED NOT NULL COMMENT 'Dia do mês em que a fatura vence',
    excluido_em DATETIME DEFAULT NULL COMMENT 'Soft-delete timestamp',
    CONSTRAINT fk_cartao_conta FOREIGN KEY (conta_pagadora_id) REFERENCES contas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cartao_id INT UNSIGNED NOT NULL,
    mes_referencia DATE NOT NULL COMMENT 'Sempre gravar como dia 01 do mês (ex: 2026-08-01)',
    valor_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    -- RN09: valor_total é a soma das transações de crédito vinculadas a esta fatura,
    -- e só impacta o saldo da conta pagadora na data_vencimento
    status ENUM('aberta','fechada','paga') NOT NULL DEFAULT 'aberta',
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    CONSTRAINT fk_fatura_cartao FOREIGN KEY (cartao_id) REFERENCES cartoes_credito(id) ON DELETE CASCADE,
    UNIQUE KEY uq_fatura_cartao_mes (cartao_id, mes_referencia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED DEFAULT NULL COMMENT 'NULL = categoria padrão do sistema, visível a todos',
    nome VARCHAR(80) NOT NULL,
    tipo ENUM('receita','despesa') NOT NULL,
    icone VARCHAR(50) DEFAULT NULL,
    cor VARCHAR(7) DEFAULT NULL COMMENT 'Hex, ex: #FF5733',
    CONSTRAINT fk_categoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Categorias padrao do sistema (usuario_id NULL -- visiveis a todo mundo).
-- Cada INSERT so roda se ainda nao existir NENHUMA categoria de sistema --
-- assim, rodar o schema de novo num banco que ja tem dados nao duplica nada.
INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Salário', 'receita', '#198754'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL);

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Outras receitas', 'receita', '#20c997'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Outras receitas');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Alimentação', 'despesa', '#fd7e14'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Alimentação');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Transporte', 'despesa', '#6f42c1'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Transporte');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Moradia', 'despesa', '#0d6efd'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Moradia');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Saúde', 'despesa', '#dc3545'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Saúde');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Lazer', 'despesa', '#d63384'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Lazer');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Educação', 'despesa', '#0dcaf0'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Educação');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Compras', 'despesa', '#ffc107'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Compras');

INSERT INTO categorias (usuario_id, nome, tipo, cor)
    SELECT NULL, 'Outras despesas', 'despesa', '#6c757d'
    WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE usuario_id IS NULL AND nome = 'Outras despesas');

CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nome VARCHAR(50) NOT NULL,
    CONSTRAINT fk_tags_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tag_usuario_nome (usuario_id, nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conta_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED DEFAULT NULL,
    fatura_id INT UNSIGNED DEFAULT NULL COMMENT 'Preenchido só quando modalidade = credito (RN09)',
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(14,2) NOT NULL COMMENT 'Sempre positivo; o "tipo" define receita/despesa',
    tipo ENUM('receita','despesa') NOT NULL,
    modalidade ENUM('pix','boleto','debito','credito','dinheiro','outro') NOT NULL DEFAULT 'outro',
    -- RN09: pix/debito -> impacta saldo imediatamente na data_fato_gerador
    -- credito -> acumula na fatura, só impacta saldo na data_vencimento
    data_fato_gerador DATE NOT NULL COMMENT 'Data em que a transação de fato ocorreu',
    data_competencia DATE NOT NULL COMMENT 'Data contábil de referência (pode ser igual à anterior)',
    status ENUM('confirmada','pendente') NOT NULL DEFAULT 'confirmada',
    -- RN08: saldo = SUM(receita confirmada) - SUM(despesa efetivada) -- calculado
    -- em tempo real via query (ver "SALDO DE CONTAS" mais abaixo), não fica
    -- armazenado numa coluna "saldo".
    excluido_em DATETIME DEFAULT NULL COMMENT 'Soft-delete: lixeira com restauracao em ate 1 dia (script de cron faz a purga definitiva depois disso)',
    origem ENUM('manual','api_openfinance') NOT NULL DEFAULT 'manual',
    id_externo VARCHAR(150) DEFAULT NULL COMMENT 'ID retornado pela API Pluggy -- RN10',
    instituicao_externa VARCHAR(100) DEFAULT NULL,
    parcela_atual TINYINT UNSIGNED DEFAULT NULL COMMENT 'Ex: 2 (a 2a parcela de um total de parcela_total) -- NULL quando a transacao nao e parcelada',
    parcela_total TINYINT UNSIGNED DEFAULT NULL COMMENT 'Quantidade total de parcelas do parcelamento -- NULL quando a transacao nao e parcelada',
    grupo_parcela_id CHAR(36) DEFAULT NULL COMMENT 'Mesmo valor em todas as parcelas de uma mesma compra parcelada',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transacao_conta FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE,
    CONSTRAINT fk_transacao_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    CONSTRAINT fk_transacao_fatura FOREIGN KEY (fatura_id) REFERENCES faturas(id) ON DELETE SET NULL,
    -- RN10: garante, no próprio banco, que o mesmo id_externo nunca é duplicado
    -- (chave única condicional -- MySQL permite múltiplos NULL em UNIQUE, então
    -- transações manuais, sem id_externo, não são afetadas por essa restrição)
    UNIQUE KEY uq_transacao_id_externo (id_externo),
    INDEX idx_transacao_conta_data (conta_id, data_fato_gerador),
    INDEX idx_transacao_excluido_em (excluido_em),
    INDEX idx_transacao_grupo_parcela (grupo_parcela_id)
) ENGINE=InnoDB;

-- Pivô N:N transação <-> tag (RN07 libera edição livre de tags mesmo em
-- transações importadas via API)
CREATE TABLE IF NOT EXISTS transacao_tags (
    transacao_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (transacao_id, tag_id),
    CONSTRAINT fk_tt_transacao FOREIGN KEY (transacao_id) REFERENCES transacoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_tt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- MÓDULO ADMIN: ACESSO DE SUPORTE COM AUDITORIA
-- ============================================================================
-- Admin NAO tem acesso irrestrito a dados privados de usuarios (saldo,
-- projetos, agenda, cartao). O unico jeito de um admin abrir um recurso
-- especifico de outra pessoa e passando por aqui: justificando o motivo,
-- ficando registrado permanentemente, e com validade curta (ver
-- LogAcessoSuporteModel::DURACAO_PADRAO_MINUTOS). O usuario pode iniciar o
-- pedido (solicitacoes_suporte, mais abaixo) e, enquanto o acesso esta
-- ativo, um chat ao vivo (mensagens_suporte) fica disponivel pros dois
-- lados conversarem.
CREATE TABLE IF NOT EXISTS log_acesso_suporte (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    usuario_alvo_id INT UNSIGNED DEFAULT NULL COMMENT 'Dono do recurso acessado (resolvido no momento da concessao)',
    tipo_recurso ENUM('projeto','conta','cartao','fatura','compromisso','plano_compra') NOT NULL,
    recurso_id INT UNSIGNED NOT NULL,
    motivo TEXT NOT NULL,
    concedido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    encerrado_em DATETIME DEFAULT NULL COMMENT 'Preenchido quando admin ou usuario encerram o suporte antes do prazo de 15min',
    CONSTRAINT fk_log_suporte_admin FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_suporte_usuario FOREIGN KEY (usuario_alvo_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_log_suporte_admin (admin_id),
    INDEX idx_log_suporte_recurso (tipo_recurso, recurso_id)
) ENGINE=InnoDB;

-- Chat ao vivo durante o atendimento de suporte -- cada concessao de acesso
-- (log_acesso_suporte) vira automaticamente uma "sessao" de chat entre o
-- admin e o usuario alvo, sem precisar de mais nenhuma tabela de sessao.
CREATE TABLE IF NOT EXISTS mensagens_suporte (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_acesso_id INT UNSIGNED NOT NULL COMMENT 'Sessao de suporte (log_acesso_suporte.id) a que esse chat pertence',
    autor_id INT UNSIGNED NOT NULL,
    mensagem TEXT NOT NULL,
    enviado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msgsuporte_log FOREIGN KEY (log_acesso_id) REFERENCES log_acesso_suporte(id) ON DELETE CASCADE,
    CONSTRAINT fk_msgsuporte_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_msgsuporte_log (log_acesso_id, id)
) ENGINE=InnoDB;

-- Fila de pedidos de suporte abertos pelo proprio usuario (perfil ->
-- "Solicitar suporte"), indicando o recurso e descrevendo o problema. O
-- admin ve isso em /Admin/solicitacoesSuporte com um botao pronto pra
-- atender; ao conceder o acesso, o pedido correspondente e marcado como
-- atendido automaticamente. 'fatura' fica de fora do enum de proposito --
-- nesse formulario o usuario escolhe por CARTAO (mais intuitivo).
CREATE TABLE IF NOT EXISTS solicitacoes_suporte (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo_recurso ENUM('projeto', 'conta', 'cartao', 'compromisso', 'plano_compra') NOT NULL,
    recurso_id INT UNSIGNED NOT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('pendente', 'atendida', 'cancelada') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendido_em DATETIME NULL DEFAULT NULL,
    atendido_por_admin_id INT UNSIGNED NULL DEFAULT NULL,
    CONSTRAINT fk_solicitsuporte_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitsuporte_admin FOREIGN KEY (atendido_por_admin_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_solicitsuporte_status (status, criado_em)
) ENGINE=InnoDB;

-- ============================================================================
-- SALDO DE CONTAS (RN08) -- calculado, sem VIEW
-- ============================================================================
-- Ate a migracao 013 existia uma VIEW (vw_saldo_contas) fazendo esse calculo.
-- Foi removida (migracao 014): alguns provedores de hospedagem compartilhada
-- nao liberam CREATE VIEW pra contas gratuitas, e o calculo em si e simples
-- o bastante pra virar so uma sub-expressao repetida direto nas queries do
-- ContaModel (constante SQL_SALDO_ATUAL) em vez de depender de um objeto
-- separado no banco. A regra e a mesma de sempre: saldo = saldo_inicial +
-- receitas confirmadas - despesas confirmadas, ignorando modalidade credito
-- (RN09, fica na fatura) e transacoes na lixeira (excluido_em).
--
-- Se este schema estiver sendo aplicado por cima de um banco que ainda tem
-- a VIEW antiga (de uma instalacao anterior a migracao 014), rode:
--   DROP VIEW IF EXISTS vw_saldo_contas;

-- ============================================================================
-- ORCAMENTO POR CATEGORIA
-- ============================================================================
-- Um limite por categoria, valido todo mes (nao precisa recriar mes a mes).
-- O gasto do mes atual e calculado na hora, comparando com transacoes.
CREATE TABLE IF NOT EXISTS orcamentos_categoria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    valor_limite DECIMAL(14,2) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orcamento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_orcamento_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    UNIQUE KEY uq_orcamento_usuario_categoria (usuario_id, categoria_id)
) ENGINE=InnoDB;

-- ============================================================================
-- TRANSACOES RECORRENTES
-- ============================================================================
-- Modelo/template de lancamento fixo (aluguel, assinatura). Um cron
-- (scripts/gerar_transacoes_recorrentes.php) confere diariamente quais
-- precisam gerar uma transacao de verdade no mes.
CREATE TABLE IF NOT EXISTS transacoes_recorrentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conta_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED DEFAULT NULL,
    cartao_id INT UNSIGNED DEFAULT NULL COMMENT 'Obrigatorio quando modalidade = credito (RN09)',
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(14,2) NOT NULL,
    tipo ENUM('receita','despesa') NOT NULL,
    modalidade ENUM('pix','boleto','debito','credito','dinheiro','outro') NOT NULL DEFAULT 'outro',
    dia_mes TINYINT UNSIGNED NOT NULL COMMENT 'Dia do mes em que deve lancar (1-31, clampado no mes curto)',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    data_inicio DATE NOT NULL,
    data_fim DATE DEFAULT NULL COMMENT 'NULL = sem data de termino',
    ultima_geracao DATE DEFAULT NULL COMMENT 'Ultimo mes em que uma transacao real foi gerada a partir daqui',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recorrencia_conta FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE,
    CONSTRAINT fk_recorrencia_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    CONSTRAINT fk_recorrencia_cartao FOREIGN KEY (cartao_id) REFERENCES cartoes_credito(id) ON DELETE SET NULL,
    INDEX idx_recorrencia_conta (conta_id)
) ENGINE=InnoDB;