<?php

namespace App\Model;

class ProjetoRelatorioModel extends BaseModel
{
    // Campos que compoem o relatorio, na ordem em que aparecem no
    // formulario e na exportacao. So 'o_que_foi_feito' e obrigatorio -- as
    // outras secoes ficam a criterio de quem escreve (nem todo projeto tem
    // "decisoes" relevantes pra registrar, por exemplo).
    const CAMPOS = ['contexto', 'o_que_foi_feito', 'decisoes', 'proximos_passos'];

    protected $validationRules = [
        'o_que_foi_feito' => ['rules' => 'required|min:10', 'label' => 'O que foi feito'],
    ];

    /**
     * buscarPorProjeto
     * Retorna o relatorio do projeto, ou array vazio se ainda nao foi
     * escrito nenhum (RN: um unico relatorio por projeto).
     *
     * @param int $projetoId
     * @return array
     */
    public function buscarPorProjeto(int $projetoId): array
    {
        $sql = "SELECT * FROM projeto_relatorios WHERE projeto_id = :projeto_id LIMIT 1";
        return $this->connDb->select($sql, ['projeto_id' => $projetoId], 'one');
    }

    /**
     * salvar
     * Cria o relatorio do projeto se ainda nao existir, ou atualiza o
     * existente (upsert) -- e assim que a RN de "um relatorio por projeto,
     * editavel" e garantida, apoiada pela UNIQUE KEY(projeto_id) no banco.
     *
     * @param int $projetoId
     * @param int $autorId
     * @param array $campos ('contexto'?, 'o_que_foi_feito', 'decisoes'?, 'proximos_passos'?)
     * @return bool
     */
    public function salvar(int $projetoId, int $autorId, array $campos): bool
    {
        $sql = "INSERT INTO projeto_relatorios
                    (projeto_id, autor_id, contexto, o_que_foi_feito, decisoes, proximos_passos)
                VALUES
                    (:projeto_id, :autor_id, :contexto, :o_que_foi_feito, :decisoes, :proximos_passos)
                ON DUPLICATE KEY UPDATE
                    autor_id         = VALUES(autor_id),
                    contexto         = VALUES(contexto),
                    o_que_foi_feito  = VALUES(o_que_foi_feito),
                    decisoes         = VALUES(decisoes),
                    proximos_passos  = VALUES(proximos_passos)";

        // Nao usamos o retorno de connDb->insert() aqui: com ON DUPLICATE KEY
        // UPDATE, o lastInsertId() do PDO pode voltar 0 quando a linha e so
        // atualizada (nao inserida) -- nao seria um indicador confiavel de
        // sucesso/falha. Se a operacao falhar de verdade, o PDO lanca
        // excecao (ATTR_ERRMODE::EXCEPTION), entao chegar ate aqui ja
        // significa que salvou.
        $this->connDb->insert($sql, [
            'projeto_id'      => $projetoId,
            'autor_id'        => $autorId,
            'contexto'        => !empty($campos['contexto']) ? $campos['contexto'] : null,
            'o_que_foi_feito' => $campos['o_que_foi_feito'],
            'decisoes'        => !empty($campos['decisoes']) ? $campos['decisoes'] : null,
            'proximos_passos' => !empty($campos['proximos_passos']) ? $campos['proximos_passos'] : null,
        ]);

        return true;
    }
}
