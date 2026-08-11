<?php

namespace App\Model;

class CompromissoRecorrenteModel extends BaseModel
{
    private const CAMPOS_EDITAVEIS = [
        'titulo', 'descricao', 'tipo', 'local', 'dia_semana',
        'hora_inicio', 'hora_fim', 'data_inicio', 'data_fim', 'notificar_email',
    ];

    protected $validationRules = [
        'titulo'      => ['rules' => 'required|min:2|max:150', 'label' => 'Titulo'],
        'dia_semana'  => ['rules' => 'required',                'label' => 'Dia da semana'],
        'hora_inicio' => ['rules' => 'required',                'label' => 'Hora de inicio'],
        'hora_fim'    => ['rules' => 'required',                'label' => 'Hora de termino'],
        'data_inicio' => ['rules' => 'required|date',           'label' => 'Valido a partir de'],
    ];

    /**
     * criar
     *
     * @param array $dados
     * @param int $usuarioId
     * @return int
     */
    public function criar(array $dados, int $usuarioId): int
    {
        $sql = "INSERT INTO compromissos_recorrentes
                    (usuario_id, titulo, descricao, tipo, local, dia_semana, hora_inicio, hora_fim,
                     data_inicio, data_fim, notificar_email)
                VALUES
                    (:usuario_id, :titulo, :descricao, :tipo, :local, :dia_semana, :hora_inicio, :hora_fim,
                     :data_inicio, :data_fim, :notificar_email)";

        return $this->connDb->insert($sql, [
            'usuario_id'      => $usuarioId,
            'titulo'          => $dados['titulo'],
            'descricao'       => $dados['descricao'] ?? null,
            'tipo'            => $dados['tipo'] ?? 'outro',
            'local'           => $dados['local'] ?? null,
            'dia_semana'      => (int) $dados['dia_semana'],
            'hora_inicio'     => $dados['hora_inicio'],
            'hora_fim'        => $dados['hora_fim'],
            'data_inicio'     => $dados['data_inicio'],
            'data_fim'        => !empty($dados['data_fim']) ? $dados['data_fim'] : null,
            'notificar_email' => !empty($dados['notificar_email']) ? 1 : 0,
        ]);
    }

    /**
     * listarPorUsuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT * FROM compromissos_recorrentes
                WHERE usuario_id = :usuario_id AND ativa = 1
                ORDER BY dia_semana ASC, hora_inicio ASC";

        return $this->connDb->select($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM compromissos_recorrentes WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
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
        $rec = $this->buscarPorId($id);
        return count($rec) > 0 && (int) $rec['usuario_id'] === $usuarioId;
    }

    /**
     * atualizar
     *
     * @param int $id
     * @param array $dados
     * @return bool
     */
    public function atualizar(int $id, array $dados): bool
    {
        $dadosPermitidos = array_intersect_key($dados, array_flip(self::CAMPOS_EDITAVEIS));

        if (empty($dadosPermitidos)) {
            return false;
        }

        if (array_key_exists('data_fim', $dadosPermitidos) && empty($dadosPermitidos['data_fim'])) {
            $dadosPermitidos['data_fim'] = null;
        }
        if (array_key_exists('notificar_email', $dadosPermitidos)) {
            $dadosPermitidos['notificar_email'] = !empty($dadosPermitidos['notificar_email']) ? 1 : 0;
        }

        $sets   = [];
        $params = ['id' => $id];
        foreach ($dadosPermitidos as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = $valor;
        }

        $sql = "UPDATE compromissos_recorrentes SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->connDb->update($sql, $params);

        return true;
    }

    /**
     * excluir
     * Remove a atividade recorrente e todos os compromissos gerados a partir dela
     * (apenas os que estiverem vinculados pela coluna recorrencia_id).
     *
     * @param int $id
     * @param int $usuarioId
     * @return bool
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        if (!$this->usuarioEhDono($id, $usuarioId)) {
            return false;
        }

        $this->connDb->delete(
            "DELETE FROM compromissos WHERE recorrencia_id = :recorrencia_id AND usuario_id = :usuario_id",
            ['recorrencia_id' => $id, 'usuario_id' => $usuarioId]
        );

        $this->connDb->delete("DELETE FROM compromissos_recorrentes WHERE id = :id", ['id' => $id]);
        return true;
    }

    /**
     * marcarGeradaAte
     *
     * @param int $id
     * @param string $data 'Y-m-d'
     * @return void
     */
    private function marcarGeradaAte(int $id, string $data): void
    {
        $sql = "UPDATE compromissos_recorrentes SET ultima_data_gerada = :data WHERE id = :id";
        $this->connDb->update($sql, ['id' => $id, 'data' => $data]);
    }

    /**
     * gerarPendentes
     * Materializa em "compromissos" de verdade as proximas ocorrencias de
     * cada recorrencia ativa, ate um horizonte de $semanasAFrente semanas a
     * partir de hoje (ou ate data_fim, o que vier primeiro). Usado tanto
     * pelo cron (scripts/gerar_compromissos_recorrentes.php) quanto pelo
     * botao manual "Gerar agora".
     *
     * RN01 (conflito de horario) e RN02 continuam valendo normalmente --
     * cada ocorrencia passa pelo CompromissoModel::criar() de verdade, que
     * ja faz essas checagens. Se uma ocorrencia especifica esbarrar num
     * conflito, ela e pulada (nao trava as outras) e reportada no resultado.
     *
     * @param \App\Model\CompromissoModel $compromissoModel
     * @param int|null $usuarioId Se informado, so gera as recorrencias
     *        desse usuario (usado pelo botao manual). O cron passa null.
     * @param int $semanasAFrente Quantas semanas pra frente manter geradas.
     * @return array Lista de ['recorrencia_id', 'titulo', 'data', 'status' => 'ok'|'pulado', 'mensagem']
     */
    public function gerarPendentes(\App\Model\CompromissoModel $compromissoModel, ?int $usuarioId = null, int $semanasAFrente = 8): array
    {
        $sql = "SELECT * FROM compromissos_recorrentes WHERE ativa = 1" . ($usuarioId !== null ? " AND usuario_id = :usuario_id" : "");
        $recorrencias = $this->connDb->select($sql, $usuarioId !== null ? ['usuario_id' => $usuarioId] : []);

        $horizonte = (new \DateTime())->modify("+{$semanasAFrente} weeks");
        $resultado = [];

        foreach ($recorrencias as $rec) {
            $dataInicioRec = new \DateTime($rec['data_inicio']);

            $cursor = !empty($rec['ultima_data_gerada'])
                ? (new \DateTime($rec['ultima_data_gerada']))->modify('+1 day')
                : clone $dataInicioRec;

            if ($cursor < $dataInicioRec) {
                $cursor = clone $dataInicioRec;
            }

            $limite = clone $horizonte;
            if (!empty($rec['data_fim'])) {
                $dataFimRec = new \DateTime($rec['data_fim']);
                if ($dataFimRec < $limite) {
                    $limite = $dataFimRec;
                }
            }

            $proxima = self::proximaOcorrenciaDoDia($cursor, (int) $rec['dia_semana']);
            $ultimaProcessada = null;

            while ($proxima <= $limite) {
                $dataStr = $proxima->format('Y-m-d');

                $r = $compromissoModel->criar([
                    'titulo'          => $rec['titulo'],
                    'descricao'       => $rec['descricao'],
                    'tipo'            => $rec['tipo'],
                    'local'           => $rec['local'],
                    'data_inicio'     => "{$dataStr} {$rec['hora_inicio']}",
                    'data_fim'        => "{$dataStr} {$rec['hora_fim']}",
                    'notificar_email' => $rec['notificar_email'],
                    'recorrencia_id'  => $rec['id'],
                ], (int) $rec['usuario_id']);

                $resultado[] = [
                    'recorrencia_id' => $rec['id'],
                    'titulo'         => $rec['titulo'],
                    'data'           => $dataStr,
                    'status'         => $r['ok'] ? 'ok' : 'pulado',
                    'mensagem'       => $r['ok'] ? 'Criado.' : $r['erro'],
                ];

                $ultimaProcessada = $dataStr;
                $proxima->modify('+7 days');
            }

            if ($ultimaProcessada !== null) {
                $this->marcarGeradaAte((int) $rec['id'], $ultimaProcessada);
            }
        }

        return $resultado;
    }

    /**
     * proximaOcorrenciaDoDia
     * Primeira data >= $apartirDe que cai no dia da semana $diaSemana
     * (0=domingo ... 6=sabado, padrao do PHP `date('w')`).
     *
     * @param \DateTime $apartirDe
     * @param int $diaSemana
     * @return \DateTime
     */
    private static function proximaOcorrenciaDoDia(\DateTime $apartirDe, int $diaSemana): \DateTime
    {
        $data = clone $apartirDe;
        $diaAtual = (int) $data->format('w');
        $diff = ($diaSemana - $diaAtual + 7) % 7;

        return $data->modify("+{$diff} days");
    }
}
