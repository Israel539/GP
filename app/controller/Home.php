<?php 
namespace App\Controller;

class Home extends BaseController
{
    public function index()
    {
        $usuarioSessao = $this->usuarioLogado();

        if (!is_array($usuarioSessao)) {
            // Visitante nao logado: so a pagina de apresentacao do sistema.
            return $this->view("home", ['estaLogado' => false]);
        }

        $usuarioId = (int) $usuarioSessao['id'];

        $compromissoModel = $this->model('Compromisso');
        $projetoModel      = $this->model('Projeto');
        $contaModel        = $this->model('Conta');
        $planoCompraModel  = $this->model('PlanoCompra');
        $usuarioModel      = $this->model('Usuario');
        $transacaoModel    = $this->model('Transacao');

        $isAdmin = $usuarioModel->isAdmin($usuarioSessao);

        $proximosCompromissos = $compromissoModel->proximosPorUsuario($usuarioId, 5);
        $totalAtrasados       = $compromissoModel->contarAtrasados($usuarioId);
        $projetos             = $projetoModel->listarPorUsuario($usuarioId, $isAdmin);
        $contas               = $contaModel->listarPorUsuario($usuarioId, $isAdmin);
        $totalPlanosCompra    = $planoCompraModel->contarPorUsuario($usuarioId);
        $resumoMes            = $transacaoModel->resumoMesPorUsuario($usuarioId);

        // "Saldo" no dashboard e a soma de TODAS as contas do usuario --
        // desde a migracao 014, "Dinheiro Fisico" e so uma conta comum que
        // a pessoa escolheu (nao existe mais um valor separado pra somar
        // aqui: a conta escolhida ja vem incluida em $contas normalmente).
        $saldoTotal = array_sum(array_column($contas, 'saldo_atual'));

        $hora = (int) date('G');
        $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

        return $this->view("home", [
            'estaLogado'           => true,
            'nome'                 => $usuarioSessao['nome'],
            'saudacao'             => $saudacao,
            'proximosCompromissos' => $proximosCompromissos,
            'totalAtrasados'       => $totalAtrasados,
            'projetosRecentes'     => array_slice($projetos, 0, 3),
            'totalProjetos'        => count($projetos),
            'totalContas'          => count($contas),
            'totalPlanosCompra'    => $totalPlanosCompra,
            'saldoTotal'           => $saldoTotal,
            'resumoMes'            => $resumoMes,
        ]);
    }

    public function contato()
    {
        header('Location: /Contato');
        exit;
    }

    public function termoPolitica()
    {
        $termoModel = $this->model('Termo');
        $termos = $termoModel->listarAtivos();

        return $this->view("termoPolitica", ['termos' => $termos]);
    }
}