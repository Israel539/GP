# Relatório de Projeto — resumo e instalação

## Visão geral

Este recurso adiciona a funcionalidade de Relatório por projeto, com formulário
de edição, exportação em PDF e `.docx`, e uma timeline (histórico) gerada a
partir de logs de atividade e mensagens do chat do projeto.

## O que foi implementado

- Botão "Relatório" no Kanban (visível apenas ao dono do projeto).
- Formulário para escrever/editar o relatório (`/ProjetoRelatorio/form/{id}`).
- Exportação em PDF (`/ProjetoRelatorio/exportarPdf/{id}`) e Word/`.docx`
  (`/ProjetoRelatorio/exportarDocx/{id}`). A exportação inclui o texto do
  relatório, a lista de participantes (nome, papel, data de entrada) e a
  timeline de atividades.
- Log de atividades novo (`projeto_atividades`) que registra eventos usados
  pela timeline (criação/conclusão de projeto, entrada/saída/remoção de
  colaboradores, criação/movimentação/exclusão de tarefas etc.).

## Regras de negócio

- Um relatório por projeto (coluna `projeto_id` com `UNIQUE KEY` em
  `projeto_relatorios`).
- Apenas o dono do projeto pode criar, editar e exportar o relatório
  (`ProjetoRelatorio::autorizado()` utiliza `ProjetoModel::usuarioEhDono()`).
- O acesso de suporte/Admin é somente para visualização (não há bypass).
- CSRF, proteção contra mass-assignment e outras validações seguem o padrão
  do restante do sistema.
- A timeline passa a registrar eventos a partir da instalação das migrations;
  eventos anteriores não são adicionados retroativamente (isso é avisado no
  rodapé dos arquivos exportados).

## Estrutura do relatório

O relatório utiliza seções guiadas em vez de um único textarea: **Contexto**,
**O que foi feito** (obrigatório), **Decisões tomadas** e **Próximos passos**.
Se uma seção estiver vazia, ela não aparece na exportação.

## Histórico e timeline

- A timeline combina eventos do log (`projeto_atividades`) com mensagens do
  chat do projeto (`MensagemProjetoModel::listarPorProjeto()`), ordenando por
  data/hora.
- No topo do histórico é exibido um resumo por tipo (ex.: número de tarefas
  criadas, mensagens no chat). A listagem detalhada é agrupada por dia.
- Se houver mais de `ProjetoRelatorio::MAX_EVENTOS_DETALHE` eventos, apenas os
  mais recentes aparecem detalhados (o resumo mostra o total real).

## Instalação

1. Rodar a migration que cria as tabelas iniciais de relatório e atividades:

   - `database/migrations/007_relatorio_projeto.sql` — cria `projeto_atividades`
     e `projeto_relatorios`.

2. Rodar a migration que altera a estrutura do conteúdo do relatório (seções):

   - `database/migrations/008_relatorio_secoes_guiadas.sql` — transforma a
     coluna `conteudo` em `o_que_foi_feito` e adiciona `contexto`, `decisoes`
     e `proximos_passos`. O texto existente é preservado em "O que foi feito".

3. Instalar dependência para exportação em Word (a exportação em PDF usa
   `dompdf`, já presente no projeto):

   ```bash
   composer require phpoffice/phpword
   composer install
   ```

## Arquivos modificados / pontos de atenção

- `composer.json` — adicionada a dependência `phpoffice/phpword`.
- `database/migrations/007_relatorio_projeto.sql` e
  `database/migrations/008_relatorio_secoes_guiadas.sql` — novas migrations.
- `app/Model/ProjetoModel.php` — adicionado `buscarProjetoIdPorTokenConvite()`
  (uso no registro de logs ao aceitar convite).
- `app/Controller/Projeto.php` — inserção de chamadas de log em eventos
  relevantes (criar, sair, remover colaborador, aceitar convite, concluir).
- `app/Controller/Tarefa.php` — inserção de chamadas de log (criar, mover,
  excluir tarefa).
- `app/view/projetoKanban.php` — botão "Relatório" exibido apenas ao dono.

## Observações finais

- A timeline só contém eventos gerados após a aplicação das migrations.
- Verifique permissões antes de testar a edição/exportação para garantir que
  o usuário é o dono do projeto.

---

Se quiser, aplico mudanças de redação adicionais ou adapto o README para um
formato diferente (ex.: changelog separado, notas de deploy).

