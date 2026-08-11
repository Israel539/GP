<?php

namespace App\Controller;

use App\Library\Session;
use App\Model\ProjetoModel;
use App\Model\TarefaModel;

class Tarefa extends BaseController
{
    protected TarefaModel $model;
    protected ProjetoModel $projetoModel;

    public function __construct()
    {
        parent::__construct();
        $this->model        = $this->model('Tarefa');
        $this->projetoModel = $this->model('Projeto');
    }

    /**
     * criar
     * Cria uma tarefa dentro de um projeto (sempre comeca em 'a_fazer').
     *
     * @return void
     */
    public function criar()
    {
        $post      = $this->request->getPost();
        $projetoId = (int) ($post['projeto_id'] ?? 0);
        $usuario   = $this->usuarioLogado();
        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        if (!$this->model->validate($post)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Projeto/kanban/{$projetoId}");
        }

        $this->model->criar([
            'projeto_id'     => $projetoId,
            'titulo'         => $post['titulo'],
            'descricao'      => $post['descricao'] ?? null,
            'responsavel_id' => !empty($post['responsavel_id']) ? (int) $post['responsavel_id'] : null,
            // Bug corrigido: input type="date" vazio manda '' no POST (a
            // chave existe, so o valor e vazio), entao '?? null' nao
            // pegava e a string vazia ia pro INSERT. Como a coluna e DATE
            // e o MySQL nao esta em modo estrito aqui, isso virava
            // silenciosamente '0000-00-00' em vez de NULL (mesmo bug que
            // ja tinha sido corrigido no atualizar(), so faltava aqui).
            'data_limite'    => !empty($post['data_limite']) ? $post['data_limite'] : null,
        ]);

        Session::set('msgSucesso', 'Tarefa criada.');
        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * mover
     * URL: /Tarefa/mover/{tarefaId}/{novoStatus}
     * RN04: TarefaModel::moverStatus() ja recusa transicao fora da sequencia.
     *
     * @return void
     */
    public function mover()
    {
        $tarefaId   = (int) $this->request->getAction();
        $novoStatus = (string) $this->request->getId();

        $tarefa = $this->model->buscarPorId($tarefaId);

        if (count($tarefa) === 0) {
            Session::set('msgError', 'Tarefa nao encontrada.');
            return header("Location: /Projeto");
        }

        $projetoId = (int) $tarefa['projeto_id'];
        $usuario   = $this->usuarioLogado();
        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $ok = $this->model->moverStatus($tarefaId, $novoStatus);

        if (!$ok) {
            Session::set('msgError', 'Movimento invalido para esta tarefa (RN04).');
        }

        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * atualizar
     * URL: /Tarefa/atualizar/{tarefaId} (POST)
     * Edita titulo, anotacoes (descricao), responsavel e prazo. Qualquer
     * participante do projeto pode editar (mesmo criterio de autorizacao
     * usado pra mover a tarefa no quadro) -- e diferente de excluir(), que
     * e restrito ao responsavel.
     *
     * @return void
     */
    public function atualizar()
    {
        $tarefaId = (int) $this->request->getAction();
        $tarefa   = $this->model->buscarPorId($tarefaId);

        if (count($tarefa) === 0) {
            Session::set('msgError', 'Tarefa nao encontrada.');
            return header("Location: /Projeto");
        }

        $projetoId = (int) $tarefa['projeto_id'];
        $usuario   = $this->usuarioLogado();

        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $post = $this->request->getPost();

        if (!$this->model->validate($post)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Projeto/kanban/{$projetoId}");
        }

        $this->model->atualizar($tarefaId, [
            'titulo'         => $post['titulo'],
            'descricao'      => $post['descricao'] ?? null,
            'responsavel_id' => !empty($post['responsavel_id']) ? (int) $post['responsavel_id'] : null,
            'data_limite'    => !empty($post['data_limite']) ? $post['data_limite'] : null,
        ]);

        Session::set('msgSucesso', 'Tarefa atualizada.');
        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * excluir
     * Permite que apenas o responsavel da tarefa a exclua dentro do projeto.
     *
     * @return void
     */
    public function excluir()
    {
        $tarefaId = (int) $this->request->getAction();
        $tarefa   = $this->model->buscarPorId($tarefaId);

        if (count($tarefa) === 0) {
            Session::set('msgError', 'Tarefa nao encontrada.');
            return header("Location: /Projeto");
        }

        $projetoId = (int) $tarefa['projeto_id'];
        $usuario   = $this->usuarioLogado();

        if (!$this->autorizado($projetoId, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        if ((int) $tarefa['responsavel_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Somente o responsavel da tarefa pode apagar esta tarefa.');
            return header("Location: /Projeto/kanban/{$projetoId}");
        }

        $ok = $this->model->excluir($tarefaId);

        if ($ok) {
            Session::set('msgSucesso', 'Tarefa excluida.');
        } else {
            Session::set('msgError', 'Nao foi possivel excluir a tarefa.');
        }

        return header("Location: /Projeto/kanban/{$projetoId}");
    }

    /**
     * autorizado
     * Criar/mover tarefa e uma ACAO -- exige participacao de verdade no
     * projeto. Acesso de suporte (Admin::suporteAcessar()) nunca da bypass
     * aqui, so serve para inspecionar (ver Projeto::podeVisualizar()).
     *
     * @param int $projetoId
     * @param int $usuarioId
     * @return bool
     */
    protected function autorizado(int $projetoId, int $usuarioId): bool
    {
        return $this->projetoModel->usuarioParticipa($projetoId, $usuarioId);
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a este projeto.');
        return header("Location: /Projeto");
    }
}
