<?php

namespace App\Controller;

use App\Library\Session;
use App\Model\ContaModel;

class Conta extends BaseController
{
    protected ContaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Conta');
        $this->helper("crud");
    }

    /**
     * index
     * Lista as contas do usuario logado, ja com o saldo calculado (RN08).
     * Admin NAO ve conta/saldo de ninguem aqui -- ver Admin::suporteAcessar().
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $contas  = $this->model->listarPorUsuario((int) $usuario['id']);

        // "Dinheiro Fisico" (RN10 -- ver migracao 014): qual conta (das que
        // ja aparecem na lista acima) o usuario escolheu pra receber
        // lancamentos com modalidade='dinheiro' automaticamente. Null se
        // ele ainda nao escolheu nenhuma.
        $contaDinheiro = $this->model->buscarContaDinheiro((int) $usuario['id']);
        $saldoContasTotal = array_sum(array_column($contas, 'saldo_atual'));

        return $this->view("contas", [
            'contas'            => $contas,
            'contaDinheiro'     => $contaDinheiro,
            'saldoContasTotal'  => $saldoContasTotal,
        ]);
    }

    /**
     * form
     * URL: /Conta/form (criar) ou /Conta/form/{id} (editar)
     *
     * @return void
     */
    public function form()
    {
        $contaId = (int) $this->request->getAction();
        $conta = null;

        if ($contaId > 0) {
            $usuario = $this->usuarioLogado();
            if (!$this->model->usuarioEhDono($contaId, (int) $usuario['id'])) {
                Session::set('msgError', 'Conta nao encontrada.');
                return header('Location: /Conta');
            }
            $conta = $this->model->buscarPorId($contaId);
        }

        return $this->view("contaForm", ['conta' => $conta]);
    }

    /**
     * salvar
     *
     * @return void
     */
    public function salvar()
    {
        $dados = $this->request->getPost();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Conta/form");
        }

        $usuario = $this->usuarioLogado();

        $dados['saldo_inicial'] = str_replace(',', '.', $dados['saldo_inicial'] ?? '0');

        $contaId = $this->model->criar($dados, (int) $usuario['id']);

        if ($contaId > 0) {
            Session::set('msgSucesso', 'Conta criada com sucesso.');
            return header("Location: /Transacao/extrato/{$contaId}");
        }

        Session::set('msgError', 'Nao foi possivel criar a conta. Tente novamente.');
        return header("Location: /Conta/form");
    }

    /**
     * atualizar
     * URL: /Conta/atualizar/{id} (POST)
     *
     * @return void
     */
    public function atualizar()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($contaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta nao encontrada.');
            return header('Location: /Conta');
        }

        $dados = $this->request->getPost();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /Conta/form/{$contaId}");
        }

        $dados['saldo_inicial'] = str_replace(',', '.', $dados['saldo_inicial'] ?? '0');

        $this->model->atualizar($contaId, $dados);

        Session::set('msgSucesso', 'Conta atualizada com sucesso.');
        return header('Location: /Conta');
    }

    /**
     * excluir
     * URL: /Conta/excluir/{id} (POST)
     * Exclusao definitiva -- apaga a conta e TODAS as transacoes ligadas a
     * ela (FK ON DELETE CASCADE). A view exige confirmacao explicita do
     * usuario antes de chegar aqui, deixando essa consequencia clara.
     *
     * @return void
     */
    public function excluir()
    {
        $contaId = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($contaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta nao encontrada.');
            return header('Location: /Conta');
        }

        $ok = $this->model->deletar($contaId);

        if ($ok) {
            Session::set('msgSucesso', 'Conta excluida com sucesso.');
        } else {
            Session::set('msgError', 'Nao foi possivel excluir a conta.');
        }

        return header('Location: /Conta');
    }

    /**
     * definirContaDinheiro
     * URL: /Conta/definirContaDinheiro (POST)
     * Marca qual conta (das que o usuario ja tem) passa a receber
     * lancamentos com modalidade='dinheiro' automaticamente (RN10 -- ver
     * migracao 014). So pode haver uma por usuario -- escolher uma nova
     * substitui a anterior.
     *
     * @return void
     */
    public function definirContaDinheiro()
    {
        $usuario = $this->usuarioLogado();
        $post    = $this->request->getPost();
        $contaId = (int) ($post['conta_id'] ?? 0);

        if (!$this->model->usuarioEhDono($contaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta nao encontrada.');
            return header('Location: /Conta');
        }

        $this->model->definirContaDinheiro((int) $usuario['id'], $contaId);

        Session::set('msgSucesso', 'Conta para Dinheiro Físico atualizada.');
        return header('Location: /Conta');
    }

    /**
     * atualizarSaldoConta
     * URL: /Conta/atualizarSaldoConta (POST)
     * Ajusta o saldo inicial para refletir o saldo atual informado pelo dono.
     * As transacoes existentes continuam preservadas e o saldo segue sendo
     * calculado a partir delas (RN08).
     *
     * @return void
     */
    public function atualizarSaldoConta()
    {
        $usuario = $this->usuarioLogado();
        $post    = $this->request->getPost();
        $contaId = (int) ($post['conta_id'] ?? 0);

        if (!$this->model->usuarioEhDono($contaId, (int) $usuario['id'])) {
            Session::set('msgError', 'Conta nao encontrada.');
            return header('Location: /Conta');
        }

        $valor = (float) str_replace(',', '.', $post['saldo_conta'] ?? '0');
        $this->model->ajustarSaldoAtual($contaId, $valor);

        Session::set('msgSucesso', 'Saldo da conta atualizado.');
        return header('Location: ' . $this->destinoAposSalvarSaldo($post));
    }

    /**
     * destinoAposSalvarSaldo
     * O ajuste de saldo aparece tanto na listagem de contas (/Conta) quanto
     * no extrato de cada conta -- 'voltar_para' (campo hidden no form) diz
     * pra onde voltar depois de salvar, senao cai no padrao (/Conta). So
     * aceita caminhos internos comecando com "/", pra ninguem usar isso
     * como redirecionador aberto pra outro site.
     *
     * @param array $post
     * @return string
     */
    protected function destinoAposSalvarSaldo(array $post): string
    {
        $destino = (string) ($post['voltar_para'] ?? '');

        if ($destino !== '' && $destino[0] === '/' && (!isset($destino[1]) || $destino[1] !== '/')) {
            return $destino;
        }

        return '/Conta';
    }
}
