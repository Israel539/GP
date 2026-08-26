<?php

namespace App\Controller;

use App\Library\Session;

class Recorrencia extends BaseController
{
    protected $model;
    protected $contaModel;
    protected $categoriaModel;
    protected $cartaoModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Recorrencia');
        $this->contaModel = $this->model('Conta');
        $this->categoriaModel = $this->model('Categoria');
        $this->cartaoModel = $this->model('CartaoCredito');
        $this->helper("crud");
    }

    /**
     * index
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $recorrencias = $this->model->listarPorUsuario((int) $usuario['id']);

        return $this->view('recorrencias', ['recorrencias' => $recorrencias]);
    }

    /**
     * form
     * @return void
     */
    public function form()
    {
        $usuario = $this->usuarioLogado();
        $contas = $this->contaModel->listarPorUsuario((int) $usuario['id']);
        $categorias = $this->categoriaModel->listarDisponiveis((int) $usuario['id']);
        $cartoes = $this->cartaoModel->listarPorUsuario((int) $usuario['id']);

        return $this->view('recorrenciaForm', ['contas' => $contas, 'categorias' => $categorias, 'cartoes' => $cartoes]);
    }

    /**
     * editar
     * URL: /Recorrencia/editar/{id}
     * @return void
     */
    public function editar()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $recorrencia = $this->model->buscarPorId($id);
        $contas = $this->contaModel->listarPorUsuario((int) $usuario['id']);
        $categorias = $this->categoriaModel->listarDisponiveis((int) $usuario['id']);
        $cartoes = $this->cartaoModel->listarPorUsuario((int) $usuario['id']);

        return $this->view('recorrenciaForm', ['recorrencia' => $recorrencia, 'contas' => $contas, 'categorias' => $categorias, 'cartoes' => $cartoes]);
    }

    /**
     * salvar
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        // RN10 (Dinheiro Fisico): recorrencia em dinheiro sempre usa a
        // conta que o usuario escolheu pra representar isso (migracao 014)
        // -- mesma regra de Transacao::lancar(). Sobrescreve o conta_id
        // vindo do formulario ANTES de checar dono, pra nem precisar que
        // o usuario escolha uma conta quando a modalidade for dinheiro.
        if (($dados['modalidade'] ?? '') === 'dinheiro') {
            $contaDinheiro = $this->contaModel->buscarContaDinheiro((int) $usuario['id']);

            if ($contaDinheiro === null) {
                Session::set('msgError', 'Voce ainda nao escolheu uma conta para representar seu Dinheiro Fisico. Configure isso na tela de Contas antes de criar uma recorrencia em dinheiro.');
                return header('Location: /Recorrencia/form');
            }

            $contaId = (int) $contaDinheiro['id'];
        } else {
            $contaId = (int) ($dados['conta_id'] ?? 0);
        }
        $dados['conta_id'] = $contaId;

        if (!$this->contaModel->usuarioEhDono($contaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta invalida.');
            return header('Location: /Recorrencia/form');
        }

        $dados['valor'] = str_replace(',', '.', $dados['valor'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /Recorrencia/form');
        }

        if (($dados['modalidade'] ?? '') === 'credito' && empty($dados['cartao_id'])) {
            Session::set('msgError', 'Selecione o cartao para uma recorrencia de credito.');
            return header('Location: /Recorrencia/form');
        }

        $this->model->criar($dados, $contaId);

        Session::set('msgSucesso', 'Recorrencia criada com sucesso.');
        return header('Location: /Recorrencia');
    }

    /**
     * atualizar
     * @return void
     */
    public function atualizar()
    {
        $dados = $this->request->getPost();
        $usuario = $this->usuarioLogado();
        $id = (int) ($dados['id'] ?? 0);

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        // RN de seguranca: 'conta_id' nunca pode vir do POST bruto aqui.
        // A tela de edicao nem mostra esse campo (conta e fixa desde a
        // criacao), entao o unico jeito legitimo de conta_id mudar e a
        // excecao controlada abaixo (RN10). Sem isso, alguem editando a
        // PROPRIA recorrencia podia forjar um conta_id de OUTRO usuario na
        // requisicao e reatribuir a recorrencia pra conta de outra pessoa
        // -- as transacoes futuras geradas por ela cairiam la.
        unset($dados['conta_id']);

        // RN10 (Dinheiro Fisico): mesma regra de salvar() -- se a modalidade
        // enviada for 'dinheiro', a recorrencia muda pra conta escolhida
        // pelo usuario (migracao 014), nao importa qual conta ela tinha antes.
        if (($dados['modalidade'] ?? '') === 'dinheiro') {
            $contaDinheiro = $this->contaModel->buscarContaDinheiro((int) $usuario['id']);

            if ($contaDinheiro === null) {
                Session::set('msgError', 'Voce ainda nao escolheu uma conta para representar seu Dinheiro Fisico. Configure isso na tela de Contas antes de mudar para essa modalidade.');
                return header('Location: /Recorrencia');
            }

            $dados['conta_id'] = (int) $contaDinheiro['id'];
        }

        $dados['valor'] = str_replace(',', '.', $dados['valor'] ?? '0');

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Recorrencia/editar/{$id}");
        }

        if (($dados['modalidade'] ?? '') === 'credito' && empty($dados['cartao_id'])) {
            Session::set('msgError', 'Selecione o cartao para uma recorrencia de credito.');
            return header("Location: /Recorrencia/editar/{$id}");
        }

        $this->model->atualizar($id, $dados);

        Session::set('msgSucesso', 'Recorrencia atualizada.');
        return header('Location: /Recorrencia');
    }

    /**
     * ativar / desativar
     * URL: /Recorrencia/alternar/{id}
     * @return void
     */
    public function alternar()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $recorrencia = $this->model->buscarPorId($id);
        $this->model->alternarAtivo($id, !$recorrencia['ativo']);

        Session::set('msgSucesso', $recorrencia['ativo'] ? 'Recorrencia pausada.' : 'Recorrencia reativada.');
        return header('Location: /Recorrencia');
    }

    /**
     * excluir
     * URL: /Recorrencia/excluir/{id}
     * @return void
     */
    public function excluir()
    {
        $id = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->pertenceAoUsuario($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->excluir($id);

        Session::set('msgSucesso', 'Recorrencia excluida.');
        return header('Location: /Recorrencia');
    }

    /**
     * gerarAgora
     * URL: /Recorrencia/gerarAgora (POST)
     * Roda a mesma logica do cron (scripts/gerar_transacoes_recorrentes.php),
     * mas so pras recorrencias do usuario logado -- util pra testar sem
     * precisar configurar o Agendador de Tarefas do Windows, e pra quem
     * esquecer de configurar o cron nao ficar sem lancar nada no mes.
     *
     * @return void
     */
    public function gerarAgora()
    {
        $usuario = $this->usuarioLogado();
        $transacaoModel = $this->model('Transacao');

        $resultados = $this->model->gerarPendentes($transacaoModel, (int) $usuario['id']);

        $okCount = count(array_filter($resultados, fn ($r) => $r['status'] === 'ok'));
        $falhas  = array_filter($resultados, fn ($r) => $r['status'] !== 'ok');

        if (empty($resultados)) {
            Session::set('msgSucesso', 'Nenhuma recorrencia pendente pra gerar hoje.');
        } elseif (empty($falhas)) {
            Session::set('msgSucesso', "{$okCount} transacao(oes) lancada(s) a partir das recorrencias.");
        } else {
            $primeiraFalha = reset($falhas);
            // htmlspecialchars na descricao -- ela e texto livre digitado pelo
            // usuario ao criar a recorrencia, e mensagens() (helper) imprime
            // essa mensagem sem escapar (de proposito, outras mensagens usam
            // HTML). 'mensagem' aqui vem sempre de excecoes com texto fixo do
            // sistema (ver TransacaoModel), entao nao precisa escapar.
            Session::set('msgError', "{$okCount} lancada(s), mas \"" . htmlspecialchars($primeiraFalha['descricao']) . "\" falhou: {$primeiraFalha['mensagem']}");
        }

        return header('Location: /Recorrencia');
    }

    /**
     * negarAcesso
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a esta recorrencia.');
        return header('Location: /Recorrencia');
    }
}
