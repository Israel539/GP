<?php

namespace App\Controller;

use App\Library\Session;
use App\Model\CompromissoRecorrenteModel;

class CompromissoRecorrente extends BaseController
{
    protected CompromissoRecorrenteModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('CompromissoRecorrente');
        $this->helper("crud");
    }

    /**
     * index
     * URL: /CompromissoRecorrente
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();

        return $this->view('compromissosRecorrentes', [
            'recorrencias' => $this->model->listarPorUsuario((int) $usuario['id']),
        ]);
    }

    /**
     * form
     * URL: /CompromissoRecorrente/form ou /CompromissoRecorrente/form/{id} (edicao)
     *
     * @return void
     */
    public function form()
    {
        $id = $this->request->getAction();
        $recorrencia = null;

        if ($id !== '' && $id !== null) {
            $usuario = $this->usuarioLogado();

            if (!$this->model->usuarioEhDono((int) $id, (int) $usuario['id'])) {
                return $this->negarAcesso();
            }

            $recorrencia = $this->model->buscarPorId((int) $id);
        }

        return $this->view('compromissoRecorrenteForm', ['recorrencia' => $recorrencia]);
    }

    /**
     * salvar
     * URL: /CompromissoRecorrente/salvar (POST)
     *
     * @return void
     */
    public function salvar()
    {
        $dados   = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header('Location: /CompromissoRecorrente/form');
        }

        if ($dados['hora_fim'] <= $dados['hora_inicio']) {
            Session::set('msgError', 'O horario de termino precisa ser depois do horario de inicio.');
            return header('Location: /CompromissoRecorrente/form');
        }

        $id = $this->model->criar($dados, (int) $usuario['id']);

        // Gera na hora as primeiras ocorrencias -- assim a pessoa ja ve o
        // resulatdo na agenda sem precisar esperar o cron rodar.
        $this->model->gerarPendentes($this->model('Compromisso'), (int) $usuario['id']);

        Session::set('msgSucesso', 'Atividade recorrente criada.');
        return header('Location: /CompromissoRecorrente');
    }

    /**
     * atualizar
     * URL: /CompromissoRecorrente/atualizar/{id} (POST)
     *
     * @return void
     */
    public function atualizar()
    {
        $id      = (int) $this->request->getAction();
        $dados   = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            return header("Location: /CompromissoRecorrente/form/{$id}");
        }

        if ($dados['hora_fim'] <= $dados['hora_inicio']) {
            Session::set('msgError', 'O horario de termino precisa ser depois do horario de inicio.');
            return header("Location: /CompromissoRecorrente/form/{$id}");
        }

        $dados['notificar_email'] = !empty($dados['notificar_email']) ? 1 : 0;

        $this->model->atualizar($id, $dados);

        Session::set('msgSucesso', 'Atividade recorrente atualizada.');
        return header('Location: /CompromissoRecorrente');
    }

    /**
     * excluir
     * URL: /CompromissoRecorrente/excluir/{id}
     *
     * @return void
     */
    public function excluir()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        $ok = $this->model->excluir($id, (int) $usuario['id']);
        Session::set($ok ? 'msgSucesso' : 'msgError', $ok ? 'Atividade recorrente removida. Os compromissos ja gerados continuam na agenda.' : 'Nao foi possivel remover.');

        return header('Location: /CompromissoRecorrente');
    }

    /**
     * gerarAgora
     * URL: /CompromissoRecorrente/gerarAgora (POST)
     * Mesma logica do cron (scripts/gerar_compromissos_recorrentes.php), so
     * pras recorrencias do usuario logado -- util pra testar sem precisar
     * configurar o Agendador de Tarefas do Windows.
     *
     * @return void
     */
    public function gerarAgora()
    {
        $usuario = $this->usuarioLogado();
        $resultados = $this->model->gerarPendentes($this->model('Compromisso'), (int) $usuario['id']);

        $okCount = count(array_filter($resultados, fn ($r) => $r['status'] === 'ok'));
        $pulados = array_filter($resultados, fn ($r) => $r['status'] !== 'ok');

        if (empty($resultados)) {
            Session::set('msgSucesso', 'Nenhuma ocorrencia nova pra gerar no momento (ja esta tudo em dia).');
        } elseif (empty($pulados)) {
            Session::set('msgSucesso', "{$okCount} compromisso(s) gerado(s) na agenda.");
        } else {
            $primeiroPulado = reset($pulados);
            // htmlspecialchars no titulo -- e texto livre digitado pelo
            // usuario ao criar a recorrencia; 'mensagem' vem sempre de
            // CompromissoModel::criar() com texto fixo do sistema.
            Session::set('msgError', "{$okCount} gerado(s), mas \"" . htmlspecialchars($primeiroPulado['titulo']) . "\" em {$primeiroPulado['data']} foi pulado: {$primeiroPulado['mensagem']}");
        }

        return header('Location: /CompromissoRecorrente');
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a este item.');
        return header('Location: /CompromissoRecorrente');
    }
}
