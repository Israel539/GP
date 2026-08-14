# GP — Gerenciador de Projetos (Personal Hub)

Sistema pessoal de gestão construído em **PHP puro, MVC autoral, sem
framework** (sem Laravel/CodeIgniter) — projeto acadêmico com autoload
PSR-4 via Composer, PDO com prepared statements, e uma estrutura de
Controller/Model/Library escrita do zero.

O GP reúne quatro pilares num só lugar: **Agenda pessoal**, **Gestão de
Projetos** (Kanban colaborativo, com relatório exportável), **Financeiro**
(contas, cartões, recorrências, planos de compra) e **Suporte** (acesso
auditado do Admin a dados de outros usuários, com chat ao vivo durante o
atendimento).

---

## Stack

- **PHP** 8.x (puro, MVC próprio — Controller/Model/Library/View)
- **MySQL / MariaDB** (PDO, prepared statements em tudo)
- **Composer** — autoload PSR-4 (`App\` → `app/`)
- **Bootstrap 5** + Bootstrap Icons (via CDN) nas views
- **Dompdf** — exportação de relatório/extrato em PDF
- **PHPWord** (`phpoffice/phpword`) — exportação de relatório em `.docx`
- **PHPMailer** — envio de e-mail (SMTP Gmail)
- JS vanilla (sem framework front-end) para os poucos pontos interativos:
  dependent-dropdowns, widget de chat, drag da caixa de chat
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
                   incluídos em toda página (footer.php também inclui o
                   widget de chat de suporte, ver mais abaixo)
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
  style.css, assets/, uploads/ (subpastas: planos/, perfil/)

scripts/
  enviar_notificacoes.php              Cron: lembretes de compromisso (RN03)
  gerar_transacoes_recorrentes.php     Cron: lança transações das recorrências
  purgar_transacoes_excluidas.php      Cron: apaga de vez transações na
                                        lixeira há mais de 24h (rodar de
                                        hora em hora, não 1x/dia)

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
- Log de atividades (`projeto_atividades`) registra automaticamente:
  criação/conclusão do projeto, entrada/saída/remoção de colaborador,
  criação/movimentação/exclusão de tarefa — alimenta a timeline do
  relatório (abaixo). Só passa a existir a partir da migration 007; eventos
  anteriores não são reconstruídos retroativamente.

#### Relatório de projeto
- Botão "Relatório" no Kanban, visível só ao **dono** do projeto.
- Formulário em seções guiadas (`/ProjetoRelatorio/form/{id}`): **Contexto**,
  **O que foi feito** (obrigatório), **Decisões tomadas**, **Próximos
  passos**. Seção vazia não aparece na exportação. Um relatório por
  projeto (`UNIQUE KEY` em `projeto_relatorios`), editável, não versionado.
- Exportação em PDF (`/ProjetoRelatorio/exportarPdf/{id}`) e Word
  (`/ProjetoRelatorio/exportarDocx/{id}`), incluindo texto do relatório,
  lista de participantes (nome, papel, data de entrada) e uma timeline que
  combina o log de atividades com as mensagens do chat do projeto,
  ordenados cronologicamente.
- A timeline exportada traz um resumo por tipo de evento no topo (contagem
  real, sempre) e a lista detalhada agrupada por dia — evita virar uma
  lista repetitiva em projetos de longa duração. Acima de
  `ProjetoRelatorio::MAX_EVENTOS_DETALHE` (200) eventos, só os mais
  recentes aparecem detalhados, com aviso.
- Só o dono pode escrever/editar/exportar (`usuarioEhDono()`); acesso de
  suporte do Admin não dá bypass nisso.

### Financeiro
- Contas, transações (receita/despesa), cartões de crédito e faturas,
  categorias, tags, orçamento
- **RN07**: transação importada via Open Finance só permite editar
  categoria/tags (o resto é imutável)
- **RN08**: saldo da conta é **sempre calculado em tempo real**
  (view `vw_saldo_contas`, `SUM(receita) - SUM(despesa)`), nunca um campo
  gravado que possa dessincronizar. A view ignora transações na lixeira
  (abaixo).
- **RN09**: transação de modalidade "crédito" gera/atualiza a fatura do mês
  correspondente automaticamente; pix/débito impactam o saldo na hora
- **RN10**: unicidade de `id_externo` (Open Finance) garantida no próprio
  banco, não só na aplicação
- **Recorrências** → viram transações reais automaticamente (cron
  `gerar_transacoes_recorrentes.php`, ou botão "Gerar agora" na tela)
- **Parcelamento no crédito**: uma compra em Nx vira N transações, uma por
  fatura/mês, com a última parcela absorvendo o arredondamento
- **Planos de compra**: metas de economia com itens filhos (ex: "Consórcio
  Moto" → "pedreiro", "material") e parcelas guardadas

#### Extrato
- Botão "+ Nova conta" na listagem de contas (`/Conta`).
- Por padrão mostra só o **mês atual**, com navegação `< >` entre meses e
  atalho "Voltar para o mês atual". Filtro por categoria continua
  disponível junto com o mês selecionado; quem precisa de um intervalo de
  datas específico (fora de um mês fechado) usa o filtro avançado.
  Exportação em CSV/PDF respeita o período em exibição.
- **Lixeira de transações**: excluir move a transação para uma lixeira
  (soft delete, coluna `excluido_em`) em vez de apagar na hora — dá pra
  **restaurar em até 1 dia**. Passado esse prazo, o cron
  `purgar_transacoes_excluidas.php` apaga em definitivo. Transação de
  crédito com fatura em aberto tem o valor descontado da fatura ao
  excluir, e devolvido se restaurada dentro do prazo.
- O PDF do extrato mostra, além do total do período filtrado, o **saldo
  atual real da conta** (RN08) em destaque no topo.

### Perfil do usuário
- `/Usuario/perfil`: dados cadastrais, foto, e ponto de entrada para
  editar perfil, trocar senha, solicitar suporte e excluir a conta.
- `/Usuario/editar`: dois formulários separados — dados (nome, e-mail,
  CPF, data de nascimento, WhatsApp, foto com upload/remoção) e troca de
  senha (exige a senha atual). Mass-assignment protegido por whitelist
  (`nivel`/`statusRegistro` ficam de fora — só Admin muda isso). E-mail
  único considerando a própria conta.

### Suporte
- Admin concede acesso pontual e auditado a um recurso de outro usuário
  (`Admin::suporteAcessar()`), por 15 minutos, com motivo obrigatório
  registrado em `log_acesso_suporte` permanentemente (`/Admin/suporteHistorico`).
- **Usuário solicita suporte** pelo próprio perfil
  (`/SolicitacaoSuporte/form`): escolhe o tipo de recurso e o item
  específico (pelo nome, não por ID) entre os que são realmente dele,
  descreve o problema. Cai numa fila (`/Admin/solicitacoesSuporte`) com
  botão "Atender" que pré-preenche o formulário de concessão de acesso. Ao
  conceder, o pedido é marcado como atendido automaticamente.
- **Chat flutuante durante o atendimento**: enquanto o acesso de suporte
  está ativo, uma caixa de chat aparece sozinha (arrastável, minimizável,
  com contador do tempo restante) para o admin e para o usuário, em
  qualquer tela que cada um estiver — não precisam estar na mesma página.
  Funciona por polling (sem WebSocket): checagem de sessão ativa a cada
  15s, mensagens a cada 4s enquanto o chat está confirmadamente ativo. Some
  para os dois lados quando o prazo expira ou quando qualquer um dos dois
  clica em "Encerrar suporte" (o que também derruba o acesso privilegiado
  do admin na hora, não só o chat).

---

## Setup local (WAMP)

1. **Clonar e instalar dependências**
   ```
   composer install
   composer require phpoffice/phpword
   ```

2. **Banco de dados**
   - Rodar `database/gpdb_schema.sql` primeiro (cria o banco `gpdb` e todas
     as tabelas)
   - Depois rodar as migrações em `database/migrations/`, **em ordem
     numérica** (cada uma tem um comentário no topo avisando se é segura
     de rodar duas vezes ou não). As mais recentes:

     | Migration | O que faz |
     |---|---|
     | `007_relatorio_projeto.sql` | Cria `projeto_atividades` e `projeto_relatorios` |
     | `008_relatorio_secoes_guiadas.sql` | `conteudo` → `o_que_foi_feito` + `contexto`, `decisoes`, `proximos_passos` |
     | `009_lixeira_transacoes.sql` | Coluna `excluido_em` em `transacoes`; recria `vw_saldo_contas` ignorando lixeira |
     | `010_chat_suporte.sql` | Coluna `encerrado_em` em `log_acesso_suporte`; cria `mensagens_suporte` |
     | `011_solicitacoes_suporte.sql` | Cria `solicitacoes_suporte` |

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
   (ou ajuste `BASEURL` conforme o seu domínio local). Sem HTTPS local, o
   Chrome pode marcar os downloads (PDF/DOCX/CSV) como "inseguros" —
   comportamento do navegador para qualquer `http://`, não é bug do
   sistema.

5. **Se der erro de SSL no cURL** (`SSL certificate problem: unable to get
   local issuer certificate`) — problema clássico de PHP no Windows, não é
   bug do projeto. Ver `app/config/README-cacert.md`.

6. **Cron jobs** (Agendador de Tarefas do Windows, ou `crontab` se for pra
   Linux/produção):
   ```
   scripts/enviar_notificacoes.php              1x/dia — lembretes de compromisso
   scripts/gerar_transacoes_recorrentes.php     1x/dia — transações recorrentes
   scripts/purgar_transacoes_excluidas.php      de hora em hora — purga da lixeira (prazo curto, 24h)
   ```
   Todos têm o comando de agendamento exato comentado no topo do arquivo.

---

## Padrões seguidos no código

Pra quem for mexer no projeto depois, os padrões abaixo se repetem em
praticamente todo Controller/Model novo — seguir eles mantém tudo
consistente:

- **CSRF** em todo POST (`Csrf::getHiddenField()` no form, validado
  automaticamente pelo `BaseController`); nos endpoints JSON chamados via
  `fetch()` (chat de suporte), o token vai embutido no `<script>` da
  página e é enviado no corpo da requisição.
- **Whitelist de campos editáveis** (`CAMPOS_EDITAVEIS` em cada Model) —
  nunca faz `UPDATE` com o `$_POST` inteiro sem filtrar
- **`usuarioEhDono()` / `podeGerenciar()`** — toda ação sensível confere
  posse antes de mexer, mesmo que a UI já esconda o botão (a checagem no
  backend é o que importa de verdade). Vale também para os pedidos de
  suporte: o backend confirma de novo que o recurso pertence a quem está
  pedindo, não confia só nas opções escondidas no formulário.
- **PDO com prepared statements** em 100% das queries
- **Soft delete + prazo de restauração** como padrão para exclusões que
  fazem sentido serem reversíveis (transações financeiras hoje; mesmo
  molde da lixeira de agenda que já existia antes) — marca uma coluna
  `excluido_em`/similar, filtra nas queries de leitura, e um script de
  cron separado faz a purga definitiva depois do prazo.
- Migrações **idempotentes quando possível** (`ADD COLUMN IF NOT EXISTS`),
  com aviso explícito em comentário quando uma linha específica não é
  (ex: `ADD CONSTRAINT`, `ADD INDEX` em MySQL puro)
- Scripts de cron/lote com **continue-on-error**: uma falha isolada não
  derruba a varredura inteira
- Views exclusivas para exportação (`*Pdf.php`) não incluem
  `comuns/header.php`/`footer.php` — Dompdf não renderiza Bootstrap/CDN
  direito, então usam HTML/CSS próprios, simples, pensados pra impressão
- Endpoints que devolvem JSON (chat de suporte) ficam em Controllers
  próprios, com um helper `json()` local que já seta o `Content-Type` e dá
  `exit` — evita HTML de erro/redirect vazando no meio de uma resposta que
  o front-end espera conseguir fazer `.json()`

---