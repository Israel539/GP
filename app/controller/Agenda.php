<?php

namespace App\Controller;

use App\Library\Session;

class Agenda extends BaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = $this->model('Compromisso');
        $this->helper("crud");
    }

    /**
     * index
     * URL: /Agenda?filtro=hoje|semana|todos
     *
     * @return void
     */
    public function index()
    {
        $usuario = $this->usuarioLogado();
        $filtro  = $_GET['filtro'] ?? 'todos';

        if (!in_array($filtro, ['hoje', 'semana', 'todos'], true)) {
            $filtro = 'todos';
        }

        $busca     = trim((string) ($this->request->getQuery('busca') ?? ''));
        $dataBusca = trim((string) ($this->request->getQuery('dataBusca') ?? ''));

        // Validacao simples da data (Y-m-d), pra nao mandar lixo pro banco
        // se alguem digitar algo fora do formato direto na URL.
        if ($dataBusca !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataBusca)) {
            $dataBusca = '';
        }

        $compromissos = $this->model->listarPorUsuario(
            (int) $usuario['id'],
            $filtro,
            $busca !== '' ? $busca : null,
            $dataBusca !== '' ? $dataBusca : null
        );
        $compromissosRecorrentes = array_values(array_filter($compromissos, fn ($c) => !empty($c['recorrencia_id'])));
        $compromissos = array_values(array_filter($compromissos, fn ($c) => empty($c['recorrencia_id'])));

        $usuarioModel = $this->model('Usuario');

        return $this->view("agenda", [
            'compromissos' => $compromissos,
            'compromissosRecorrentes' => $compromissosRecorrentes,
            'filtro' => $filtro,
            'busca' => $busca,
            'dataBusca' => $dataBusca,
            'limpezaAutomaticaAtiva' => $usuarioModel->agendaLimpezaAutomaticaAtiva((int) $usuario['id']),
        ]);
    }

    /**
     * calendario
     * URL: /Agenda/calendario?ano=2026&mes=8
     * Visualizacao em grid mensal (tipo Google Calendar). Sem parametros,
     * mostra o mes atual.
     *
     * @return void
     */
    public function calendario()
    {
        $usuario = $this->usuarioLogado();

        $hoje = new \DateTime();
        $ano  = (int) ($this->request->getQuery('ano') ?? $hoje->format('Y'));
        $mes  = (int) ($this->request->getQuery('mes') ?? $hoje->format('n'));

        if ($mes < 1 || $mes > 12) {
            $mes = (int) $hoje->format('n');
        }

        $primeiroDiaMes = \DateTime::createFromFormat('Y-n-j', "{$ano}-{$mes}-1");
        $primeiroDiaMes->setTime(0, 0, 0);

        $mesAnterior  = (clone $primeiroDiaMes)->modify('-1 month');
        $mesSeguinte  = (clone $primeiroDiaMes)->modify('+1 month');

        $compromissos = $this->model->listarPorMes((int) $usuario['id'], $ano, $mes);

        // Agrupa por dia (Y-m-d) pra view so percorrer o array do dia certo.
        $porDia = [];
        foreach ($compromissos as $c) {
            $diaChave = date('Y-m-d', strtotime($c['data_inicio']));
            $porDia[$diaChave][] = $c;
        }

        // Feriados nacionais (BrasilAPI, com cache local -- ver FeriadoService).
        // Busca tambem o ano do mes anterior/seguinte, pra cobrir os dias "de
        // fora" que o grid mostra quando o mes vira dezembro/janeiro.
        $anosVisiveis = [
            (int) $mesAnterior->format('Y'),
            $ano,
            (int) $mesSeguinte->format('Y'),
        ];

        $feriados = \App\Library\FeriadoService::doIntervaloDeAnos($anosVisiveis);

        // Datas comemorativas (Dia das Maes, Namorados, etc.) -- nao sao
        // feriado oficial, entao nao vem da API, mas sao uteis mostrar.
        // Ver DataComemorativaService.
        $comemorativas = [];
        foreach (array_unique($anosVisiveis) as $anoComemorativa) {
            $comemorativas += \App\Library\DataComemorativaService::doAno((int) $anoComemorativa);
        }

        return $this->view("agendaCalendario", [
            'ano'           => $ano,
            'mes'           => $mes,
            'primeiroDia'   => $primeiroDiaMes,
            'mesAnterior'   => $mesAnterior,
            'mesSeguinte'   => $mesSeguinte,
            'porDia'        => $porDia,
            'feriados'      => $feriados,
            'comemorativas' => $comemorativas,
            'hojeChave'     => $hoje->format('Y-m-d'),
        ]);
    }

    /**
     * form
     * URL: /Agenda/form ou /Agenda/form/{id} (edicao)
     * Aceita ?data=YYYY-MM-DD (vindo de um clique no calendario) pra
     * pre-preencher o inicio de um compromisso novo.
     *
     * @return void
     */
    public function form()
    {
        $idParaEditar = $this->request->getAction();
        $compromisso  = null;

        if ($idParaEditar !== "" && $idParaEditar !== null) {
            $usuario = $this->usuarioLogado();

            // Visualizar (nao editar de fato -- isso so acontece em salvar())
            // aceita acesso de suporte auditado, alem do proprio dono.
            if (!$this->model->usuarioEhDono((int) $idParaEditar, (int) $usuario['id'])
                && !$this->temAcessoSuporteAtivo('compromisso', (int) $idParaEditar)) {
                return $this->negarAcesso();
            }

            $compromisso = $this->model->buscarPorId((int) $idParaEditar);
        }

        $dataPreenchida = $this->request->getQuery('data');

        return $this->view("compromissoForm", [
            'compromisso'    => $compromisso,
            'dataPreenchida' => $dataPreenchida,
        ]);
    }

    /**
     * salvar
     * Cria OU atualiza, dependendo se veio um campo "id" oculto no form.
     *
     * @return void
     */
    public function salvar()
    {
        $dados   = $this->request->getPost();
        $usuario = $this->usuarioLogado();

        if (!$this->model->validate($dados)) {
            Session::set('msgError', 'Verifique os campos destacados e tente novamente.');
            $redirectId = !empty($dados['id']) ? "/{$dados['id']}" : "";
            return header("Location: /Agenda/form{$redirectId}");
        }

        $idExistente = !empty($dados['id']) ? (int) $dados['id'] : null;

        if ($idExistente !== null) {
            if (!$this->model->usuarioEhDono($idExistente, (int) $usuario['id'])) {
                return $this->negarAcesso();
            }

            $resultado = $this->model->atualizar($idExistente, $dados, (int) $usuario['id']);
        } else {
            $resultado = $this->model->criar($dados, (int) $usuario['id']);
        }

        if (!$resultado['ok']) {
            Session::set('msgError', $resultado['erro']);
            $redirectId = $idExistente !== null ? "/{$idExistente}" : "";
            return header("Location: /Agenda/form{$redirectId}");
        }

        Session::set('msgSucesso', $idExistente !== null ? 'Compromisso atualizado.' : 'Compromisso criado.');
        return header("Location: /Agenda");
    }

    /**
     * concluir
     * URL: /Agenda/concluir/{id}
     *
     * @return void
     */
    public function concluir()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->concluir($id, (int) $usuario['id']);
        Session::set('msgSucesso', 'Compromisso marcado como concluido.');
        return header("Location: /Agenda");
    }

    /**
     * cancelar
     * URL: /Agenda/cancelar/{id}
     *
     * @return void
     */
    public function cancelar()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->cancelar($id, (int) $usuario['id']);
        Session::set('msgSucesso', 'Compromisso cancelado.');
        return header("Location: /Agenda");
    }

    /**
     * excluir
     * URL: /Agenda/excluir/{id}
     *
     * @return void
     */
    public function excluir()
    {
        $id      = (int) $this->request->getAction();
        $usuario = $this->usuarioLogado();

        if (!$this->model->usuarioEhDono($id, (int) $usuario['id'])) {
            return $this->negarAcesso();
        }

        $this->model->excluir($id, (int) $usuario['id']);
        Session::set('msgSucesso', 'Compromisso excluido.');
        return header("Location: /Agenda");
    }

    /**
     * excluirEmMassa
     * URL: /Agenda/excluirEmMassa (POST)
     * Recebe ids[] (checkboxes marcados na listagem) e apaga so os que
     * pertencem ao usuario logado -- ver CompromissoModel::excluirEmMassa().
     *
     * @return void
     */
    public function excluirEmMassa()
    {
        $usuario = $this->usuarioLogado();
        $dados   = $this->request->getPost();
        $ids     = $dados['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            Session::set('msgError', 'Selecione ao menos um compromisso para excluir.');
            return header('Location: /Agenda');
        }

        $totalExcluido = $this->model->excluirEmMassa($ids, (int) $usuario['id']);

        if ($totalExcluido > 0) {
            Session::set('msgSucesso', $totalExcluido . ' compromisso(s) excluido(s).');
        } else {
            Session::set('msgError', 'Nenhum compromisso pode ser excluido.');
        }

        return header('Location: /Agenda');
    }

    /**
     * configurarLimpezaAutomatica
     * URL: /Agenda/configurarLimpezaAutomatica (POST)
     * Liga/desliga a preferencia do usuario de excluir automaticamente (via
     * cron) os compromissos concluidos ha mais de 30 dias. Ver migracao 005
     * e scripts/limpar_compromissos_concluidos.php.
     *
     * @return void
     */
    public function configurarLimpezaAutomatica()
    {
        $usuario = $this->usuarioLogado();
        $dados   = $this->request->getPost();
        $ativa   = !empty($dados['agenda_limpeza_automatica']);

        $this->model('Usuario')->definirLimpezaAutomaticaAgenda((int) $usuario['id'], $ativa);

        Session::set('msgSucesso', $ativa
            ? 'Exclusao automatica ativada: compromissos concluidos ha mais de 30 dias serao apagados pelo cron.'
            : 'Exclusao automatica desativada.');

        return header('Location: /Agenda');
    }

    /**
     * negarAcesso
     *
     * @return void
     */
    protected function negarAcesso()
    {
        http_response_code(403);
        Session::set('msgError', 'Voce nao tem acesso a este compromisso.');
        return header("Location: /Agenda");
    }
}
