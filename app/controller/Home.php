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
        $usuarioModel      = $this->model('Usuario');

        $isAdmin = $usuarioModel->isAdmin($usuarioSessao);

        $proximosCompromissos = $compromissoModel->proximosPorUsuario($usuarioId, 5);
        $projetos             = $projetoModel->listarPorUsuario($usuarioId, $isAdmin);
        $contas               = $contaModel->listarPorUsuario($usuarioId, $isAdmin);

        $saldoTotal = array_sum(array_column($contas, 'saldo_atual'));

        return $this->view("home", [
            'estaLogado'           => true,
            'nome'                 => $usuarioSessao['nome'],
            'proximosCompromissos' => $proximosCompromissos,
            'totalProjetos'        => count($projetos),
            'totalContas'          => count($contas),
            'saldoTotal'           => $saldoTotal,
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