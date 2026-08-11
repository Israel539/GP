# GP — Gerenciador de Projetos (Personal Hub)

Sistema pessoal de gestão construído em **PHP puro, MVC autoral, sem
framework** (sem Laravel/CodeIgniter) — projeto acadêmico com autoload
PSR-4 via Composer, PDO com prepared statements, e uma estrutura de
Controller/Model/Library escrita do zero.

O GP reúne três pilares num só lugar: **Agenda pessoal**, **Gestão de
Projetos** (Kanban colaborativo) e **Financeiro** (contas, cartões,
recorrências, planos de compra).

---

## Stack

- **PHP** 8.x (puro, MVC próprio — Controller/Model/Library/View)
- **MySQL / MariaDB** (PDO, prepared statements em tudo)
- **Composer** — autoload PSR-4 (`App\` → `app/`)
- **Bootstrap 5** + Bootstrap Icons (via CDN) nas views
- **Dompdf** — exportação de relatórios em PDF
- **PHPMailer** — envio de e-mail (SMTP Gmail)
- Ambiente de desenvolvimento local: **WAMP** (Windows)

---

## Estrutura de pastas

```
app/
  Controller/     Um controller por recurso (Agenda, Transacao, Projeto, ...)
  Model/          Uma classe por tabela/entidade, todas extendem BaseModel
  Library/        Infra própria: Database, Session, Csrf, Validator, Routes,
                   Request, Mailer, FeriadoService, WhatsappGateway, ...
  view/           Templates PHP (Bootstrap), comuns/header.php e footer.php
                   incluídos em toda página
  helper/         Funções soltas carregadas sob demanda (ex: crud.php)
  config/         config.php (única fonte de constantes/config) + READMEs
                   de setup (cacert.pem, WhatsApp)

database/
  gpdb_schema.sql       Schema completo, comentado com as RNs que cada
                         tabela sustenta
  migrations/            Migrações incrementais (rodar em ordem numérica,
                         uma vez cada — ver comentário no topo de cada arquivo)

public/
  index.php        Front controller (tudo passa por aqui via .htaccess)
  style.css, assets/, uploads/

scripts/
  enviar_notificacoes.php            Cron: lembretes de compromisso (RN03)
  gerar_transacoes_recorrentes.php   Cron: lança transações das recorrências

tests/              Scripts de smoke test manuais (não é PHPUnit)
```

---

## Módulos e principais regras de negócio (RN)

### Agenda
- Compromissos com data/hora, tipo, local
- **RN01**: não permite dois compromissos do tipo "reunião presencial" no
  mesmo horário
- **RN02**: `data_fim` sempre depois de `data_inicio`
- **RN03**: notificação por e-mail 24h antes (flag de envio evita reenvio)
- Visualização em **lista** e em **calendário mensal** (grid tipo Google
  Calendar), com **feriados nacionais** (BrasilAPI, com cache local) e
  **datas comemorativas** (calculadas localmente) marcados automaticamente

### Projetos (Kanban)
- Quadro A Fazer / Em Andamento / Concluído, colaboradores por convite
  (e-mail com token), chat por projeto
- **RN04**: transição de status da tarefa só pode seguir a sequência válida
- **RN05**: projeto só pode ser concluído se não sobrar tarefa pendente
- **RN06**: tarefa atrasada é calculada dinamicamente (`data_limite < hoje`),
  não é um status gravado
- Tarefas têm campo de anotações (visível só ao abrir o modal "Ver", pra
  não poluir o card)

### Financeiro
- Contas, transações (receita/despesa), cartões de crédito e faturas,
  categorias, tags, orçamento
- **RN07**: transação importada via Open Finance só permite editar
  categoria/tags (o resto é imutável)
- **RN08**: saldo da conta é **sempre calculado em tempo real**
  (`SUM(receita) - SUM(despesa)`), nunca um campo gravado que possa
  dessincronizar
- **RN09**: transação de modalidade "crédito" gera/atualiza a fatura do mês
  correspondente automaticamente; pix/débito impactam o saldo na hora
- **RN10**: unicidade de `id_externo` (Open Finance) garantida no próprio
  banco, não só na aplicação
- **Recorrências** → viram transações reais automaticamente (cron
  `gerar_transacoes_recorrentes.php`, ou botão "Gerar agora" na tela)
- **Parcelamento no crédito**: uma compra em Nx vira N transações, uma por
  fatura/mês, com a última parcela absorvendo o arredondamento
- **Exportação** do extrato em CSV e PDF, por período
- **Planos de compra**: metas de economia com itens filhos (ex: "Consórcio
  Moto" → "pedreiro", "material") e parcelas guardadas

### Admin
- Suporte com acesso auditado a dados de outros usuários (log de acesso),
  nível admin separado do perfil comum

---

## Setup local (WAMP)

1. **Clonar e instalar dependências**
   ```
   composer install
   ```

2. **Banco de dados**
   - Rodar `database/gpdb_schema.sql` primeiro (cria o banco `gpdb` e todas
     as tabelas)
   - Depois rodar as migrações em `database/migrations/`, **em ordem
     numérica** (cada uma tem um comentário no topo avisando se é segura
     de rodar duas vezes ou não)

3. **`.env`** (copiar as chaves abaixo, preencher os valores — o
   `app/config/config.php` já tem default sensato pra quase tudo):
   ```
   BASEURL=http://gp/

   DB_DRIVE=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_USER=root
   DB_PSW=
   DB_BDADOS=gpdb

   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_SMTPSECURE=tls
   MAIL_SMTP_AUTH=true
   MAIL_USER=
   MAIL_SENHA=
   MAIL_NOME=Projeto GP
   ```
   (WhatsApp está com o código presente mas **desativado** por decisão do
   projeto — só e-mail está ativo. Ver `app/config/README-whatsapp.md` se
   um dia quiser reativar.)

4. **Virtual host** apontando pra raiz do projeto (o `.htaccess` já
   redireciona tudo pra `public/index.php`), acessível como `http://gp/`
   (ou ajuste `BASEURL` conforme o seu domínio local).

5. **Se der erro de SSL no cURL** (`SSL certificate problem: unable to get
   local issuer certificate`) — problema clássico de PHP no Windows, não é
   bug do projeto. Ver `app/config/README-cacert.md`.

6. **Cron jobs** (Agendador de Tarefas do Windows, ou `crontab` se for pra
   Linux/produção):
   ```
   scripts/enviar_notificacoes.php            (lembretes de compromisso)
   scripts/gerar_transacoes_recorrentes.php   (transações recorrentes)
   ```
   Ambos têm o comando de agendamento exato comentado no topo do arquivo.

---

## Padrões seguidos no código

Pra quem for mexer no projeto depois, os padrões abaixo se repetem em
praticamente todo Controller/Model novo — seguir eles mantém tudo
consistente:

- **CSRF** em todo POST (`Csrf::getHiddenField()` no form, validado
  automaticamente pelo `BaseController`)
- **Whitelist de campos editáveis** (`CAMPOS_EDITAVEIS` em cada Model) —
  nunca faz `UPDATE` com o `$_POST` inteiro sem filtrar
- **`usuarioEhDono()` / `podeGerenciar()`** — toda ação sensível confere
  posse antes de mexer, mesmo que a UI já esconda o botão (a checagem no
  backend é o que importa de verdade)
- **PDO com prepared statements** em 100% das queries
- Migrações **idempotentes quando possível** (`ADD COLUMN IF NOT EXISTS`),
  com aviso explícito em comentário quando uma linha específica não é
  (ex: `ADD CONSTRAINT`, `ADD INDEX` em MySQL puro)
- Scripts de cron/lote com **continue-on-error**: uma falha isolada não
  derruba a varredura inteira

---

## Scripts de diagnóstico

`tmp_db_check.php` e `tmp_feriados_check.php` na raiz são scripts
temporários de debug (acessados direto pelo navegador ou `php nome.php`),
não fazem parte da aplicação — dá ppra aagar quando não precisar mais
deles.
