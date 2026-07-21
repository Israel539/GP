<?php

namespace App\Controller;

use App\Library\Session;

class PlanoCompra extends BaseController
{
    protected $model;
    protected $usuarioModel;
    protected $parcelaModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('PlanoCompra');
        $this->usuarioModel = $this->model('Usuario');
        $this->parcelaModel = $this->model('ParcelaPlanoCompra');
    }

    /**
     * carregarPlanoOuNegar
     * Centraliza a checagem "o plano existe e pertence a quem esta logado?"
     * que antes estava duplicada em 7 metodos diferentes. Se autorizado,
     * devolve o array do plano; se nao, ja registra a mensagem de erro e o
     * redirect (igual cada metodo fazia antes), e devolve null -- quem
     * chamar so precisa checar `=== null` e dar `return`.
     *
     * @param int $id
     * @return array|null
     */
    private function carregarPlanoOuNegar(int $id): ?array
    {
        $usuario = $this->usuarioLogado();
        $plano = $this->model->buscarPorId($id);

        if (empty($plano) || (int) $plano['usuario_id'] !== (int) $usuario['id']) {
            http_response_code(403);
            Session::set('msgError', 'Plano nao encontrado ou sem permissao.');
            header('Location: /PlanoCompra');
            return null;
        }

        return $plano;
    }

    /**
     * carregarPlanoVisualizavelOuNegar
     * Mesma coisa, mas para VISUALIZACAO (ver/editar form) -- aceita tambem
     * um admin com acesso de suporte ativo e especifico (Admin::suporteAcessar()).
     * Acoes (atualizar/excluir/restaurar/concluir/cancelar) continuam usando
     * carregarPlanoOuNegar() acima, sem esse bypass.
     *
     * @param int $id
     * @return array|null
     */
    private function carregarPlanoVisualizavelOuNegar(int $id): ?array
    {
        $usuario = $this->usuarioLogado();
        $plano = $this->model->buscarPorId($id);

        $ehDono = !empty($plano) && (int) $plano['usuario_id'] === (int) $usuario['id'];

        if (!$ehDono && !$this->temAcessoSuporteAtivo('plano_compra', $id)) {
            http_response_code(403);
            Session::set('msgError', 'Plano nao encontrado ou sem permissao.');
            header('Location: /PlanoCompra');
            return null;
        }

        return $plano;
    }

    public function index()
    {
        $usuario = $this->usuarioLogado();
        $mostrarExcluidos = $this->request->getQuery('show') === 'excluidos';
        $search = trim((string) $this->request->getQuery('search') ?? '');
        $page = max(1, (int) ($this->request->getQuery('page') ?? 1));
        $perPage = 6;

        $planos = $this->model->listarPorUsuario((int) $usuario['id'], $search, $page, $perPage, $mostrarExcluidos);
        $totalPlanos = $this->model->contarPorUsuario((int) $usuario['id'], $search, $mostrarExcluidos);
        $excluidosCount = $this->model->contarExcluidosPorUsuario((int) $usuario['id']);
        $totalPages = (int) ceil($totalPlanos / $perPage);

        return $this->view('planosCompra', [
            'planos' => $planos,
            'mostrarExcluidos' => $mostrarExcluidos,
            'excluidosCount' => $excluidosCount,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function form()
    {
        $parentId = (int) ($this->request->getQuery('parent_id') ?? 0);
        $planoPai = null;

        if ($parentId > 0) {
            $planoPai = $this->carregarPlanoOuNegar($parentId);
            if ($planoPai === null) {
                return;
            }
        }

        return $this->view('planoCompraForm', ['planoPai' => $planoPai]);
    }

    public function editar()
    {
        $planoId = (int) $this->request->getAction();

        $plano = $this->carregarPlanoVisualizavelOuNegar($planoId);
        if ($plano === null) {
            return;
        }

        return $this->view('planoCompraForm', ['plano' => $plano]);
    }

    public function atualizar()
    {
        $dados = $this->request->getPost();

        $id = (int) ($dados['id'] ?? 0);
        if ($id <= 0) {
            Session::set('msgError', 'ID do plano invalido.');
            return header('Location: /PlanoCompra');
        }

        if ($this->carregarPlanoOuNegar($id) === null) {
            return;
        }

        $dados['valor_total'] = str_replace(',', '.', $dados['valor_total'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /PlanoCompra/editar/' . $id);
        }

        $ok = $this->model->atualizar($id, $dados);

        if ($ok) {
            Session::set('msgSucesso', 'Plano atualizado com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel atualizar o plano.');
        }

        return header('Location: /PlanoCompra');
    }

    public function excluir()
    {
        $planoId = (int) $this->request->getAction();

        if ($this->carregarPlanoOuNegar($planoId) === null) {
            return;
        }

        $ok = $this->model->deletar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano excluido com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel excluir o plano.');
        }

        return header('Location: /PlanoCompra');
    }

    public function restaurar()
    {
        $planoId = (int) $this->request->getAction();

        if ($this->carregarPlanoOuNegar($planoId) === null) {
            return;
        }

        $ok = $this->model->restaurar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano restaurado com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar o plano (provavelmente o periodo de restauração expirou).');
        }

        return header('Location: /PlanoCompra');
    }

    public function restaurarTodos()
    {
        $usuario = $this->usuarioLogado();
        $ok = $this->model->restaurarTodosPorUsuario((int) $usuario['id']);

        if ($ok) {
            Session::set('msgSucesso', 'Todos os planos excluídos foram restaurados com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel restaurar os planos excluidos.');
        }

        return header('Location: /PlanoCompra?show=excluidos');
    }

    public function salvar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        $parentId = !empty($dados['parent_id']) ? (int) $dados['parent_id'] : null;

        if ($parentId !== null && $this->carregarPlanoOuNegar($parentId) === null) {
            return;
        }

        $dados['valor_total'] = str_replace(',', '.', $dados['valor_total'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            $voltarPara = $parentId ? "/PlanoCompra/form?parent_id={$parentId}" : '/PlanoCompra/form';
            return header("Location: {$voltarPara}");
        }

        $planoId = $this->model->criar($dados, (int) $usuario['id'], $parentId);

        if ($planoId > 0) {
            Session::set('msgSucesso', 'Plano de compra salvo com sucesso.');
            $destino = $parentId ? "/PlanoCompra/ver/{$parentId}" : '/PlanoCompra';
            return header("Location: {$destino}");
        }

        Session::set('msgError', 'Nao foi possivel salvar o plano. Tente novamente.');
        return header('Location: /PlanoCompra/form');
    }

    public function ver()
    {
        $planoId = (int) $this->request->getAction();

        $plano = $this->carregarPlanoVisualizavelOuNegar($planoId);
        if ($plano === null) {
            return;
        }

        $filhos = $this->model->listarFilhos($planoId);
        $temFilhos = count($filhos) > 0;

        $parcelas = $this->parcelaModel->listarPorPlano($planoId);
        $valorGuardado = $this->parcelaModel->somarPorPlano($planoId);
        $valorTotal = (float) $plano['valor_total'];

        // Se tem itens dentro (ex: Cozinha tem Micro-ondas + Geladeira), o
        // progresso mostrado e o AGREGADO da arvore inteira, nao so do
        // proprio registro (que pode nem ter meta propria, so servir de
        // categoria/pasta).
        if ($temFilhos) {
            $totaisAgregados = $this->model->totaisComFilhos($planoId);
            $valorTotal = $totaisAgregados['valor_total'];
            $valorGuardado = $totaisAgregados['valor_guardado'];
        }

        $progresso = $valorTotal > 0 ? min(100, ($valorGuardado / $valorTotal) * 100) : 0;

        $planoPai = !empty($plano['parent_id']) ? $this->model->buscarPorId((int) $plano['parent_id']) : null;

        return $this->view('planoCompraDetalhe', [
            'plano'           => $plano,
            'planoPai'        => $planoPai,
            'filhos'          => $filhos,
            'temFilhos'       => $temFilhos,
            'parcelas'        => $parcelas,
            'valorGuardado'   => $valorGuardado,
            'valorRestante'   => max(0, $valorTotal - $valorGuardado),
            'valorTotalExibido' => $valorTotal,
            'progresso'       => $progresso,
        ]);
    }

    /**
     * adicionarParcela
     * URL: POST /PlanoCompra/adicionarParcela/{planoId}
     * Registra um deposito/parcela guardado rumo a meta. So o dono do plano
     * pode fazer isso (acao, nao aceita bypass de acesso de suporte).
     *
     * @return void
     */
    public function adicionarParcela()
    {
        $planoId = (int) $this->request->getAction();

        if ($this->carregarPlanoOuNegar($planoId) === null) {
            return;
        }

        $dados = $this->request->getPost();
        $dados['valor'] = str_replace(',', '.', $dados['valor'] ?? '0');

        if (!$this->parcelaModel->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /PlanoCompra/ver/' . $planoId);
        }

        $this->parcelaModel->criar($dados, $planoId);
        $this->model->atualizarProgresso($planoId);

        Session::set('msgSucesso', 'Parcela adicionada com sucesso.');
        return header('Location: /PlanoCompra/ver/' . $planoId);
    }

    /**
     * excluirParcela
     * URL: POST /PlanoCompra/excluirParcela/{parcelaId}
     *
     * @return void
     */
    public function excluirParcela()
    {
        $parcelaId = (int) $this->request->getAction();
        $parcela = $this->parcelaModel->buscarPorId($parcelaId);

        if (count($parcela) === 0) {
            Session::set('msgError', 'Parcela nao encontrada.');
            return header('Location: /PlanoCompra');
        }

        $planoId = (int) $parcela['plano_compra_id'];

        if ($this->carregarPlanoOuNegar($planoId) === null) {
            return;
        }

        $this->parcelaModel->excluir($parcelaId);
        $this->model->atualizarProgresso($planoId);

        Session::set('msgSucesso', 'Parcela removida.');
        return header('Location: /PlanoCompra/ver/' . $planoId);
    }

    public function concluir()
    {
        $planoId = (int) $this->request->getAction();

        if ($this->carregarPlanoOuNegar($planoId) === null) {
            return;
        }

        $ok = $this->model->finalizar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano marcado como concluido.');
        } else {
            Session::set('msgError', 'Nao foi possivel concluir o plano.');
        }

        return header('Location: /PlanoCompra');
    }

    public function cancelar()
    {
        $planoId = (int) $this->request->getAction();

        if ($this->carregarPlanoOuNegar($planoId) === null) {
            return;
        }

        $ok = $this->model->cancelar($planoId);

        if ($ok) {
            Session::set('msgSucesso', 'Plano cancelado.');
        } else {
            Session::set('msgError', 'Nao foi possivel cancelar o plano.');
        }

        return header('Location: /PlanoCompra');
    }
}