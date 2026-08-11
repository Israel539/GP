<?php

namespace App\Model;

class ProjetoAtividadeModel extends BaseModel
{
    // Tipos de evento registrados na timeline. Sao usados so como marcador
    // interno (ex: pra estilizar a linha do tempo por categoria no PDF/DOCX
    // se quiser no futuro) -- o texto que aparece pro usuario e sempre a
    // 'descricao', ja formatada no momento em que o evento acontece.
    const TIPO_PROJETO_CRIADO       = 'projeto_criado';
    const TIPO_PROJETO_CONCLUIDO    = 'projeto_concluido';
    const TIPO_COLABORADOR_ENTROU   = 'colaborador_entrou';
    const TIPO_COLABORADOR_SAIU     = 'colaborador_saiu';
    const TIPO_COLABORADOR_REMOVIDO = 'colaborador_removido';
    const TIPO_TAREFA_CRIADA        = 'tarefa_criada';
    const TIPO_TAREFA_MOVIDA        = 'tarefa_movida';
    const TIPO_TAREFA_EXCLUIDA      = 'tarefa_excluida';

    /**
     * registrar
     * Grava um evento na timeline do projeto. Chamado pelos Controllers
     * (Projeto, Tarefa) logo apos a acao correspondente ter sido concluida
     * com sucesso -- nunca antes, pra nao logar coisa que nao aconteceu de
     * fato (ex: tarefa que falhou validacao).
     *
     * @param int $projetoId
     * @param int|null $usuarioId Quem fez a acao. NULL para eventos de sistema.
     * @param string $tipo Uma das const TIPO_*
     * @param string $descricao Texto pronto pra exibir na timeline
     * @return int Id do registro criado
     */
    public function registrar(int $projetoId, ?int $usuarioId, string $tipo, string $descricao): int
    {
        $sql = "INSERT INTO projeto_atividades (projeto_id, usuario_id, tipo, descricao)
                VALUES (:projeto_id, :usuario_id, :tipo, :descricao)";

        return $this->connDb->insert($sql, [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
            'tipo'       => $tipo,
            'descricao'  => $descricao,
        ]);
    }

    /**
     * listarPorProjeto
     * Timeline completa do projeto, ordem cronologica (mais antiga primeiro
     * -- e assim que um "caminho percorrido" deve ler). Autor pode vir NULL
     * (autor_nome) se o usuario que gerou o evento foi excluido depois.
     *
     * @param int $projetoId
     * @return array
     */
    public function listarPorProjeto(int $projetoId): array
    {
        $sql = "SELECT a.*, u.nome AS autor_nome
                FROM projeto_atividades a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.projeto_id = :projeto_id
                ORDER BY a.criado_em ASC, a.id ASC";

        return $this->connDb->select($sql, ['projeto_id' => $projetoId]);
    }
}
