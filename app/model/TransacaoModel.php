<?php

namespace App\Model;

class TransacaoModel extends BaseModel
{
    // Campos que uma transacao importada via API NUNCA pode ter alterados
    // manualmente (RN07). So sobra categoria_id e as tags (via TagModel).
    private const CAMPOS_IMUTAVEIS_API = [
        'valor', 'tipo', 'modalidade', 'data_fato_gerador',
        'data_competencia', 'id_externo', 'instituicao_externa', 'conta_id',
    ];

    protected $validationRules = [
        'descricao' => ['rules' => 'required|min:2|max:200', 'label' => 'Descricao'],
        'valor'     => ['rules' => 'required|numeric',        'label' => 'Valor'],
    ];

    /**
     * criarManual
     * Lancamento feito a mao pelo usuario (ou chamado internamente pelo
     * FaturaModel::pagar() para o debito do pagamento da fatura).
     *
     * RN09: se modalidade = 'credito', a transacao NUNCA mexe no saldo da
     * conta -- ela e roteada para a fatura do cartao (via FaturaModel) e so
     * impacta o saldo quando a fatura for paga.
     *
     * @param array $dados ('conta_id','descricao','valor','tipo','modalidade',
     *                      'data_fato_gerador','data_competencia','categoria_id'?,
     *                      'cartao_id' obrigatorio se modalidade = credito)
     * @return int
     */
    public function criarManual(array $dados): int
    {
        $faturaId = null;

        if (($dados['modalidade'] ?? '') === 'credito') {
            if (empty($dados['cartao_id'])) {
                throw new \InvalidArgumentException('cartao_id e obrigatorio para transacao de modalidade credito.');
            }

            $faturaModel = new FaturaModel();
            $faturaId = $faturaModel->buscarOuCriarAberta(
                (int) $dados['cartao_id'],
                $dados['data_competencia'] ?? $dados['data_fato_gerador']
            );

            $faturaModel->adicionarValor($faturaId, (float) $dados['valor']);
        }

        $sql = "INSERT INTO transacoes
                    (conta_id, categoria_id, fatura_id, descricao, valor, tipo, modalidade,
                     data_fato_gerador, data_competencia, status, origem)
                VALUES
                    (:conta_id, :categoria_id, :fatura_id, :descricao, :valor, :tipo, :modalidade,
                     :data_fato_gerador, :data_competencia, :status, 'manual')";

        return $this->connDb->insert($sql, [
            'conta_id'          => $dados['conta_id'],
            'categoria_id'      => $dados['categoria_id'] ?? null,
            'fatura_id'         => $faturaId,
            'descricao'         => $dados['descricao'],
            'valor'             => $dados['valor'],
            'tipo'              => $dados['tipo'],
            'modalidade'        => $dados['modalidade'] ?? 'outro',
            'data_fato_gerador' => $dados['data_fato_gerador'],
            'data_competencia'  => $dados['data_competencia'] ?? $dados['data_fato_gerador'],
            'status'            => $dados['status'] ?? 'confirmada',
        ]);
    }

    /**
     * sincronizarViaApi
     * RN10: antes de inserir, verifica se o id_externo ja existe. Se ja
     * existir, aborta e devolve null -- e o proprio Model que garante isso,
     * nao so a UNIQUE KEY do banco (que e a rede de seguranca final).
     *
     * @param array $dados Payload ja mapeado do Pluggy (ver documento RN)
     * @param int $contaId Conta local a qual esse payload pertence
     * @return int|null Id da transacao criada, ou null se ja existia (RN10)
     */
    public function sincronizarViaApi(array $dados, int $contaId): ?int
    {
        $sql = "SELECT id FROM transacoes WHERE id_externo = :id_externo LIMIT 1";
        $existente = $this->connDb->select($sql, ['id_externo' => $dados['id_externo']], 'one');

        if (count($existente) > 0) {
            return null; // RN10: duplicidade -- aborta silenciosamente
        }

        $faturaId = null;

        if (($dados['modalidade'] ?? '') === 'credito' && !empty($dados['cartao_id'])) {
            $faturaModel = new FaturaModel();
            $faturaId = $faturaModel->buscarOuCriarAberta(
                (int) $dados['cartao_id'],
                $dados['data_competencia'] ?? $dados['data_fato_gerador']
            );
            $faturaModel->adicionarValor($faturaId, (float) $dados['valor']);
        }

        $sqlInsert = "INSERT INTO transacoes
                        (conta_id, categoria_id, fatura_id, descricao, valor, tipo, modalidade,
                         data_fato_gerador, data_competencia, status, origem, id_externo, instituicao_externa)
                      VALUES
                        (:conta_id, :categoria_id, :fatura_id, :descricao, :valor, :tipo, :modalidade,
                         :data_fato_gerador, :data_competencia, :status, 'api_openfinance', :id_externo, :instituicao_externa)";

        try {
            return $this->connDb->insert($sqlInsert, [
                'conta_id'             => $contaId,
                'categoria_id'         => $dados['categoria_id'] ?? null,
                'fatura_id'            => $faturaId,
                'descricao'            => $dados['descricao'],
                'valor'                => abs((float) $dados['valor']),
                'tipo'                 => $dados['tipo'],
                'modalidade'           => $dados['modalidade'] ?? 'outro',
                'data_fato_gerador'    => $dados['data_fato_gerador'],
                'data_competencia'     => $dados['data_competencia'] ?? $dados['data_fato_gerador'],
                'status'               => $dados['status'] ?? 'confirmada',
                'id_externo'           => $dados['id_externo'],
                'instituicao_externa'  => $dados['instituicao_externa'] ?? null,
            ]);
        } catch (\PDOException $ex) {
            // RN10, segunda linha de defesa: se por alguma corrida de sincronizacao
            // dois processos tentarem inserir o mesmo id_externo ao mesmo tempo,
            // a UNIQUE KEY do banco recusa o segundo INSERT antes mesmo de checar aqui.
            return null;
        }
    }

    /**
     * buscarPorId
     *
     * @param int $id
     * @return array
     */
    public function buscarPorId(int $id): array
    {
        $sql = "SELECT * FROM transacoes WHERE id = :id LIMIT 1";
        return $this->connDb->select($sql, ['id' => $id], 'one');
    }

    /**
     * listarPorConta
     *
     * @param int $contaId
     * @param array $filtros ('data_inicio'?, 'data_fim'?, 'categoria_id'?)
     * @return array
     */
    public function listarPorConta(int $contaId, array $filtros = []): array
    {
        $sql = "SELECT t.*, c.nome AS categoria_nome
                FROM transacoes t
                LEFT JOIN categorias c ON c.id = t.categoria_id
                WHERE t.conta_id = :conta_id";

        $params = ['conta_id' => $contaId];

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND t.data_fato_gerador >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND t.data_fato_gerador <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND t.categoria_id = :categoria_id";
            $params['categoria_id'] = $filtros['categoria_id'];
        }

        $sql .= " ORDER BY t.data_fato_gerador DESC, t.id DESC";

        return $this->connDb->select($sql, $params);
    }

    /**
     * resumoMesPorUsuario
     * Usado no dashboard da Home -- soma receitas e despesas confirmadas do
     * mes corrente, em TODAS as contas do usuario (JOIN, ja que transacoes
     * nao guarda usuario_id direto). Inclui transacoes de qualquer
     * modalidade (mesmo credito) -- diferente da RN08/vw_saldo_contas, aqui
     * a ideia e mostrar o fluxo de gasto do mes, nao o saldo em caixa.
     *
     * @param int $usuarioId
     * @return array ['receitas' => float, 'despesas' => float]
     */
    public function resumoMesPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT t.tipo, COALESCE(SUM(t.valor), 0) AS total
                FROM transacoes t
                INNER JOIN contas c ON c.id = t.conta_id
                WHERE c.usuario_id = :usuario_id
                  AND t.status = 'confirmada'
                  AND MONTH(t.data_fato_gerador) = MONTH(CURDATE())
                  AND YEAR(t.data_fato_gerador) = YEAR(CURDATE())
                GROUP BY t.tipo";

        $linhas = $this->connDb->select($sql, ['usuario_id' => $usuarioId]);

        $resumo = ['receitas' => 0.0, 'despesas' => 0.0];
        foreach ($linhas as $linha) {
            $resumo[$linha['tipo'] === 'receita' ? 'receitas' : 'despesas'] = (float) $linha['total'];
        }

        return $resumo;
    }

    /**
     * listarPorFatura
     * Transacoes de credito que compoe uma fatura especifica.
     *
     * @param int $faturaId
     * @return array
     */
    public function listarPorFatura(int $faturaId): array
    {
        $sql = "SELECT t.*, c.nome AS categoria_nome
                FROM transacoes t
                LEFT JOIN categorias c ON c.id = t.categoria_id
                WHERE t.fatura_id = :fatura_id
                ORDER BY t.data_fato_gerador DESC";

        return $this->connDb->select($sql, ['fatura_id' => $faturaId]);
    }

    /**
     * atualizar
     * RN07: se a transacao vier de origem = 'api_openfinance', filtra do
     * array recebido qualquer campo que nao seja categoria_id -- mesmo que
     * o Controller (por bug ou tentativa indevida) tenha mandado mais coisa.
     * O Model e a ultima linha de defesa da regra, nao confia so na View/JS.
     *
     * @param int $transacaoId
     * @param array $dados
     * @return bool
     */
    public function atualizar(int $transacaoId, array $dados): bool
    {
        $transacao = $this->buscarPorId($transacaoId);

        if (count($transacao) === 0) {
            return false;
        }

        if ($transacao['origem'] === 'api_openfinance') {
            foreach (self::CAMPOS_IMUTAVEIS_API as $campoProibido) {
                unset($dados[$campoProibido]);
            }
        } else {
            // Em transacoes manuais, nao permitimos mudar a modalidade de credito
            // para outra coisa nem criar credito no update. Isso evita inconsistencias
            // com faturas e o fluxo RN09.
            if (!empty($dados['modalidade']) && $dados['modalidade'] === 'credito' && $transacao['modalidade'] !== 'credito') {
                throw new \InvalidArgumentException('Alterar para modalidade credito nao e permitido em transacoes existentes. Crie uma nova transacao de credito.');
            }

            if ($transacao['modalidade'] === 'credito' && !empty($dados['modalidade']) && $dados['modalidade'] !== 'credito') {
                throw new \InvalidArgumentException('Nao e permitido alterar a modalidade de uma transacao de credito.');
            }

            if ($transacao['modalidade'] === 'credito' && isset($dados['valor'])) {
                $novoValor = (float) $dados['valor'];
                $valorAntigo = (float) $transacao['valor'];
                $diferenca = $novoValor - $valorAntigo;

                if (!empty($transacao['fatura_id']) && abs($diferenca) > 0.0001) {
                    $faturaModel = new FaturaModel();
                    $faturaModel->adicionarValor((int) $transacao['fatura_id'], $diferenca);
                }
            }
        }

        if (empty($dados)) {
            return true; // nada permitido restou para atualizar -- nao e erro, e RN07 fazendo efeito
        }

        // Monta o SET dinamicamente so com os campos que sobraram (ex: so 'categoria_id',
        // ou 'descricao'+'categoria_id' no caso de uma transacao manual).
        $sets = [];
        $params = ['id' => $transacaoId];

        foreach ($dados as $campo => $valor) {
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = $valor;
        }

        $sql = "UPDATE transacoes SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->connDb->update($sql, $params);

        return true;
    }

    /**
     * excluir
     * Por coerencia com o espirito da RN07, transacoes vindas da API nao
     * podem ser excluidas manualmente (assim como nao podem ser editadas) --
     * so lancamentos manuais podem. Se uma transacao de credito manual for
     * excluida antes da fatura ser paga, desconta o valor da fatura tambem,
     * para nao deixar residuo cobrado indevidamente.
     *
     * @param int $transacaoId
     * @return bool
     */
    public function excluir(int $transacaoId): bool
    {
        $transacao = $this->buscarPorId($transacaoId);

        if (count($transacao) === 0) {
            return false;
        }

        if ($transacao['origem'] === 'api_openfinance') {
            return false; // RN07: imutavel, inclusive para exclusao
        }

        if (!empty($transacao['fatura_id'])) {
            $faturaModel = new FaturaModel();
            $fatura = $faturaModel->buscarPorId((int) $transacao['fatura_id']);

            if (count($fatura) > 0 && $fatura['status'] !== FaturaModel::STATUS_PAGA) {
                $faturaModel->adicionarValor((int) $transacao['fatura_id'], -1 * (float) $transacao['valor']);
            }
        }

        $sql = "DELETE FROM transacoes WHERE id = :id";
        $this->connDb->delete($sql, ['id' => $transacaoId]);

        return true;
    }
}
