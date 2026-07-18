<?php

namespace App\Model;

class CompromissoModel extends BaseModel
{
    const TIPO_REUNIAO_PRESENCIAL = 'reuniao_presencial';
    const TIPO_TAREFA_PESSOAL     = 'tarefa_pessoal';
    const TIPO_LEMBRETE           = 'lembrete';
    const TIPO_OUTRO              = 'outro';

    const STATUS_PENDENTE  = 'pendente';
    const STATUS_CONCLUIDO = 'concluido';
    const STATUS_CANCELADO = 'cancelado';

    protected $validationRules = [
        'titulo'      => ['rules' => 'required|min:2|max:150', 'label' => 'Titulo'],
        'data_inicio' => ['rules' => 'required',                'label' => 'Data/hora de inicio'],
        'data_fim'    => ['rules' => 'required',                'label' => 'Data/hora de termino'],
    ];

    /**
     * criar
     * RN02: recusa se data_fim <= data_inicio.
     * RN01: recusa se tipo = reuniao_presencial e ja existir outro compromisso
     * ativo do mesmo usuario no mesmo intervalo.
     *
     * @param array $dados
     * @param int $usuarioId
     * @return array ['ok' => bool, 'erro' => string|null, 'id' => int|null]
     */
    public function criar(array $dados, int $usuarioId): array
    {
        $erroPeriodo = $this->validarPeriodo($dados['data_inicio'], $dados['data_fim']);
        if ($erroPeriodo !== null) {
            return ['ok' => false, 'erro' => $erroPeriodo, 'id' => null];
        }

        $tipo = $dados['tipo'] ?? self::TIPO_OUTRO;

        if ($tipo === self::TIPO_REUNIAO_PRESENCIAL) {
            if ($this->existeConflitoDeHorario($usuarioId, $dados['data_inicio'], $dados['data_fim'])) {
                return [
                    'ok'   => false,
                    'erro' => 'RN01: ja existe outro compromisso presencial marcado nesse mesmo horario.',
                    'id'   => null,
                ];
            }
        }

        $sql = "INSERT INTO compromissos
                    (usuario_id, titulo, descricao, tipo, data_inicio, data_fim, local, status,
                     notificar_whatsapp, notificar_email)
                VALUES
                    (:usuario_id, :titulo, :descricao, :tipo, :data_inicio, :data_fim, :local, :status,
                     :notificar_whatsapp, :notificar_email)";

        $id = $this->connDb->insert($sql, [
            'usuario_id'         => $usuarioId,
            'titulo'             => $dados['titulo'],
            'descricao'          => $dados['descricao'] ?? null,
            'tipo'               => $tipo,
            'data_inicio'        => $dados['data_inicio'],
            'data_fim'           => $dados['data_fim'],
            'local'              => $dados['local'] ?? null,
            'status'             => self::STATUS_PENDENTE,
            'notificar_whatsapp' => !empty($dados['notificar_whatsapp']) ? 1 : 0,
            'notificar_email'    => !empty($dados['notificar_email']) ? 1 : 0,
        ]);

        return ['ok' => true, 'erro' => null, 'id' => $id];
    }

    /**
     * atualizar
     * Mesmas RN01/RN02 do criar(), mas ignorando o proprio registro na
     * checagem de conflito de horario.
     *
     * @param int $id
     * @param array $dados
     * @param int $usuarioId
     * @return array ['ok' => bool, 'erro' => string|null]
     */
    public function atualizar(int $id, array $dados, int $usuarioId): array
    {
        $erroPeriodo = $this->validarPeriodo($dados['data_inicio'], $dados['data_fim']);
        if ($erroPeriodo !== null) {
            return ['ok' => false, 'erro' => $erroPeriodo];
        }

        $tipo = $dados['tipo'] ?? self::TIPO_OUTRO;

        if ($tipo === self::TIPO_REUNIAO_PRESENCIAL) {
            if ($this->existeConflitoDeHorario($usuarioId, $dados['data_inicio'], $dados['data_fim'], $id)) {
                return ['ok' => false, 'erro' => 'RN01: ja existe outro compromisso presencial marcado nesse mesmo horario.'];
            }
        }

        $sql = "UPDATE compromissos SET
                    titulo = :titulo, descricao = :descricao, tipo = :tipo,
                    data_inicio = :data_inicio, data_fim = :data_fim, local = :local,
                    notificar_whatsapp = :notificar_whatsapp, notificar_email = :notificar_email
                WHERE id = :id AND usuario_id = :usuario_id";

        $this->connDb->update($sql, [
            'titulo'             => $dados['titulo'],
            'descricao'          => $dados['descricao'] ?? null,
            'tipo'               => $tipo,
            'data_inicio'        => $dados['data_inicio'],
            'data_fim'           => $dados['data_fim'],
            'local'              => $dados['local'] ?? null,
            'notificar_whatsapp' => !empty($dados['notificar_whatsapp']) ? 1 : 0,
            'notificar_email'    => !empty($dados['notificar_email']) ? 1 : 0,
            'id'                 => $id,
            'usuario_id'         => $usuarioId,
        ]);

        return ['ok' => true, 'erro' => null];
    }

    /**
     * validarPeriodo
     * RN02: data/hora de termino precisa ser estritamente posterior ao inicio.
     *
     * @param string $dataInicio
     * @param string $dataFim
     * @return string|null Mensagem de erro, ou null se estiver valido
     */
    private function validarPeriodo(string $dataInicio, string $dataFim): ?string
    {
        if (strtotime($dataFim) <= strtotime($dataInicio)) {
            return 'RN02: a data/hora de termino precisa ser depois da data/hora de inicio.';
        }

        return null;
    }

    /**
     * existeConflitoDeHorario
     * RN01: overlap classico de intervalos -- dois periodos se cruzam quando
     * um comeca antes do outro terminar E termina depois do outro comecar.
     * So considera compromissos 'pendente' (cancelado/concluido nao contam
     * como "ativo") e do tipo reuniao_presencial.
     *
     * @param int $usuarioId
     * @param string $dataInicio
     * @param string $dataFim
     * @param int|null $idIgnorar Id do proprio compromisso, ao editar
     * @return bool
     */
    private function existeConflitoDeHorario(int $usuarioId, string $dataInicio, string $dataFim, ?int $idIgnorar = null): bool
    {
        $sql = "SELECT id FROM compromissos
                WHERE usuario_id = :usuario_id
                  AND tipo = :tipo
                  AND status = :status
                  AND data_inicio < :data_fim
                  AND data_fim > :data_inicio";

        $params = [
            'usuario_id' => $usuarioId,
            'tipo'       => self::TIPO_REUNIAO_PRESENCIAL,
            'status'     => self::STATUS_PENDENTE,
            'data_fim'   => $dataFim,
            'data_inicio' => $dataInicio,
        ];

        if ($idIgnorar !== null) {
            $sql .= " AND id != :id_ignorar";
            $params['id_ignorar'] = $idIgnorar;
        }

        $sql .= " LIMIT 1";

        $linha = $this->connDb->select($sql, $params, 'one');
        return count($linha) > 0;
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM compromissos WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorUsuario
     *
     * @param int $usuarioId
     * @param string $filtro 'hoje'|'semana'|'todos'
     * @return array
     */
    public function listarPorUsuario(int $usuarioId, string $filtro = 'todos'): array
    {
        $sql = "SELECT * FROM compromissos WHERE usuario_id = :usuario_id";
        $params = ['usuario_id' => $usuarioId];

        if ($filtro === 'hoje') {
            $sql .= " AND DATE(data_inicio) = CURDATE()";
        } elseif ($filtro === 'semana') {
            $sql .= " AND data_inicio BETWEEN CURDATE() AND (CURDATE() + INTERVAL 7 DAY)";
        }

        $sql .= " ORDER BY data_inicio ASC, id ASC";

        return $this->connDb->select($sql, $params);
    }

    /**
     * proximosPorUsuario
     * Usado no dashboard da Home -- so os N proximos compromissos pendentes,
     * a partir de agora.
     *
     * @param int $usuarioId
     * @param int $limite
     * @return array
     */
    public function proximosPorUsuario(int $usuarioId, int $limite = 5): array
    {
        $sql = "SELECT * FROM compromissos
                WHERE usuario_id = :usuario_id
                  AND status = :status
                  AND data_inicio >= NOW()
                ORDER BY data_inicio ASC
                LIMIT " . (int) $limite;

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId, 'status' => self::STATUS_PENDENTE]);
    }

    /**
     * contarAtrasados
     * Usado no dashboard da Home -- RN06 (mesmo espirito do "Atrasado" das
     * tarefas de projeto, aqui aplicado a compromissos): pendente + prazo
     * final ja passou.
     *
     * @param int $usuarioId
     * @return int
     */
    public function contarAtrasados(int $usuarioId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM compromissos
                WHERE usuario_id = :usuario_id
                  AND status = :status
                  AND data_fim < NOW()";

        $linha = $this->connDb->select($sql, ['usuario_id' => $usuarioId, 'status' => self::STATUS_PENDENTE], 'one');
        return (int) ($linha['total'] ?? 0);
    }

    /**
     * concluir
     *
     * @param int $id
     * @param int $usuarioId
     * @return void
     */
    public function concluir(int $id, int $usuarioId): void
    {
        $sql = "UPDATE compromissos SET status = :status WHERE id = :id AND usuario_id = :usuario_id";
        $this->connDb->update($sql, ['status' => self::STATUS_CONCLUIDO, 'id' => $id, 'usuario_id' => $usuarioId]);
    }

    /**
     * cancelar
     *
     * @param int $id
     * @param int $usuarioId
     * @return void
     */
    public function cancelar(int $id, int $usuarioId): void
    {
        $sql = "UPDATE compromissos SET status = :status WHERE id = :id AND usuario_id = :usuario_id";
        $this->connDb->update($sql, ['status' => self::STATUS_CANCELADO, 'id' => $id, 'usuario_id' => $usuarioId]);
    }

    /**
     * excluir
     *
     * @param int $id
     * @param int $usuarioId
     * @return void
     */
    public function excluir(int $id, int $usuarioId): void
    {
        $sql = "DELETE FROM compromissos WHERE id = :id AND usuario_id = :usuario_id";
        $this->connDb->delete($sql, ['id' => $id, 'usuario_id' => $usuarioId]);
    }

    /**
     * usuarioEhDono
     *
     * @param int $id
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioEhDono(int $id, int $usuarioId): bool
    {
        $compromisso = $this->buscarPorId($id);
        return count($compromisso) > 0 && (int) $compromisso['usuario_id'] === $usuarioId;
    }

    /**
     * listarParaNotificar
     * RN03: usado pelo script de cron. So pega compromissos 'pendente' cujo
     * inicio cai dentro da janela [agora, agora + $horasAntes horas] e que
     * AINDA NAO foram notificados no canal pedido -- e essa flag
     * (notificado_*_em) que impede o mesmo aviso ser mandado 2x (spam).
     *
     * @param string $canal 'email'|'whatsapp'
     * @param int $horasAntes
     * @return array Cada linha inclui o e-mail/telefone do usuario (JOIN)
     */
    public function listarParaNotificar(string $canal, int $horasAntes = 24): array
    {
        $colunaFlag = $canal === 'whatsapp' ? 'notificado_whatsapp_em' : 'notificado_email_em';
        $colunaPref = $canal === 'whatsapp' ? 'notificar_whatsapp' : 'notificar_email';

        $sql = "SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email, u.telefone_whats AS usuario_telefone
                FROM compromissos c
                INNER JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.status = :status
                  AND c.{$colunaPref} = 1
                  AND c.{$colunaFlag} IS NULL
                  AND c.data_inicio BETWEEN NOW() AND (NOW() + INTERVAL :horas HOUR)";

        return $this->connDb->select($sql, ['status' => self::STATUS_PENDENTE, 'horas' => $horasAntes]);
    }

    /**
     * marcarNotificado
     * RN03: registra a flag de envio, para o proximo ciclo do cron nao
     * mandar a mesma notificacao de novo.
     *
     * @param int $id
     * @param string $canal 'email'|'whatsapp'
     * @return void
     */
    public function marcarNotificado(int $id, string $canal): void
    {
        $coluna = $canal === 'whatsapp' ? 'notificado_whatsapp_em' : 'notificado_email_em';

        $sql = "UPDATE compromissos SET {$coluna} = NOW() WHERE id = :id";
        $this->connDb->update($sql, ['id' => $id]);
    }
}
