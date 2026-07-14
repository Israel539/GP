<?php
// Script de teste manual dos Models do Projeto GP, direto contra um banco
// MySQL/MariaDB real (nao e PHPUnit, e um roteiro sequencial que verifica
// as RNs criticas). Requer o schema de gpdb_schema.sql ja carregado no banco
// configurado em app/config/config.php.
// Rodar a partir da raiz do projeto com: php tests/test_models.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\UsuarioModel;
use App\Model\ProjetoModel;
use App\Model\TarefaModel;
use App\Model\MensagemProjetoModel;
use App\Model\ContaModel;
use App\Model\CategoriaModel;
use App\Model\TagModel;
use App\Model\CartaoCreditoModel;
use App\Model\FaturaModel;
use App\Model\TransacaoModel;
use App\Model\PlanoCompraModel;

$totalTestes = 0;
$totalFalhas = 0;

function checar(string $descricao, bool $condicao): void
{
    global $totalTestes, $totalFalhas;
    $totalTestes++;
    if ($condicao) {
        echo "  [OK] {$descricao}\n";
    } else {
        $totalFalhas++;
        echo "  [FALHOU] {$descricao}\n";
    }
}

echo "========================================\n";
echo "1. UsuarioModel + Admin\n";
echo "========================================\n";

$usuarioModel = new UsuarioModel();

$idAna = $usuarioModel->insert([
    'nome' => 'Ana Colaboradora', 'email' => 'ana+' . time() . '@teste.com', 'senha' => 'senha123',
]);
$idBeto = $usuarioModel->insert([
    'nome' => 'Beto Dono', 'email' => 'beto+' . time() . '@teste.com', 'senha' => 'senha123',
]);
checar('Ana criada com id > 0', $idAna > 0);
checar('Beto criado com id > 0', $idBeto > 0);

$anaEncontrada = $usuarioModel->getUserEmail($usuarioModel->buscarPorId($idAna)['email']);
checar('getUserEmail encontra a Ana', (int) $anaEncontrada['id'] === $idAna);
checar('Novo usuario nasce NIVEL_COMUM (nao admin por padrao)', !$usuarioModel->isAdmin($anaEncontrada));

$usuarioModel->alterarNivel($idBeto, UsuarioModel::NIVEL_ADMIN);
$betoAtualizado = $usuarioModel->buscarPorId($idBeto);
checar('alterarNivel promove Beto a admin', (int) $betoAtualizado['nivel'] === UsuarioModel::NIVEL_ADMIN);

$usuarioModel->alterarStatus($idAna, UsuarioModel::STATUS_INATIVO);
checar('alterarStatus bloqueia a Ana', (int) $usuarioModel->buscarPorId($idAna)['statusRegistro'] === UsuarioModel::STATUS_INATIVO);
$usuarioModel->alterarStatus($idAna, UsuarioModel::STATUS_ATIVO); // reverte pros proximos testes

echo "\n========================================\n";
echo "2. ProjetoModel -- colaboracao + RN05\n";
echo "========================================\n";

$projetoModel = new ProjetoModel();
$tarefaModel  = new TarefaModel();

$idProjeto = $projetoModel->criar(['nome' => 'App de Gestao Pessoal'], $idBeto);
checar('Projeto criado com id > 0', $idProjeto > 0);
checar('Dono (Beto) ja participa automaticamente', $projetoModel->usuarioParticipa($idProjeto, $idBeto));
checar('Ana ainda NAO participa', !$projetoModel->usuarioParticipa($idProjeto, $idAna));

$token = $projetoModel->convidar($idProjeto, $idBeto, 'ana@teste.com');
checar('Token de convite gerado', strlen($token) > 10);

$aceitou = $projetoModel->aceitarConvite($token, $idAna);
checar('Ana aceita o convite', $aceitou === true);
checar('Ana agora participa do projeto', $projetoModel->usuarioParticipa($idProjeto, $idAna));

$aceitouDeNovo = $projetoModel->aceitarConvite($token, $idAna);
checar('Reaproveitar o mesmo token nao duplica vinculo', $aceitouDeNovo === false);

$idTarefa1 = $tarefaModel->criar(['projeto_id' => $idProjeto, 'titulo' => 'Modelar banco', 'responsavel_id' => $idBeto]);
$idTarefa2 = $tarefaModel->criar(['projeto_id' => $idProjeto, 'titulo' => 'Criar Models', 'responsavel_id' => $idAna]);
checar('Duas tarefas criadas', $idTarefa1 > 0 && $idTarefa2 > 0);

$concluiuComPendencia = $projetoModel->concluir($idProjeto);
checar('RN05: projeto NAO conclui com tarefas pendentes', $concluiuComPendencia === false);

echo "\n========================================\n";
echo "3. TarefaModel -- RN04 (maquina de estados) + RN06 (atraso)\n";
echo "========================================\n";

$moveuDireto = $tarefaModel->moverStatus($idTarefa1, 'concluido'); // pular etapa: a_fazer -> concluido
checar('RN04: NAO deixa pular de a_fazer direto pra concluido', $moveuDireto === false);

$moveu1 = $tarefaModel->moverStatus($idTarefa1, 'em_andamento');
$moveu2 = $tarefaModel->moverStatus($idTarefa1, 'concluido');
checar('RN04: a_fazer -> em_andamento -> concluido funciona em sequencia', $moveu1 && $moveu2);

$tarefaModel->moverStatus($idTarefa2, 'em_andamento'); // deixa a 2 em andamento, pendente

$tarefaAtrasadaId = $tarefaModel->criar([
    'projeto_id' => $idProjeto, 'titulo' => 'Tarefa vencida', 'data_limite' => '2020-01-01',
]);
$tarefas = $tarefaModel->listarPorProjeto($idProjeto);
$tarefaVencida = current(array_filter($tarefas, fn($t) => (int) $t['id'] === $tarefaAtrasadaId));
checar('RN06: tarefa com prazo no passado e nao concluida aparece como atrasada', $tarefaVencida['atrasada'] === true);

$tarefaModel->moverStatus($tarefaAtrasadaId, 'em_andamento');
$tarefaModel->moverStatus($tarefaAtrasadaId, 'concluido');
$tarefaModel->moverStatus($idTarefa2, 'concluido');

$concluiuAgora = $projetoModel->concluir($idProjeto);
checar('RN05: agora que nao ha mais tarefa pendente, projeto conclui', $concluiuAgora === true);

echo "\n========================================\n";
echo "4. MensagemProjetoModel (chat)\n";
echo "========================================\n";

$msgModel = new MensagemProjetoModel();
$msgModel->enviar($idProjeto, $idBeto, 'Bem-vinda ao projeto!');
$msgModel->enviar($idProjeto, $idAna, 'Obrigada, vamos comecar.');
$mensagens = $msgModel->listarPorProjeto($idProjeto);
checar('Duas mensagens no chat, em ordem cronologica', count($mensagens) === 2 && $mensagens[0]['mensagem'] === 'Bem-vinda ao projeto!');

echo "\n========================================\n";
echo "5. Financeiro -- RN08/RN09 (saldo e cartao de credito)\n";
echo "========================================\n";

$contaModel     = new ContaModel();
$categoriaModel = new CategoriaModel();
$tagModel       = new TagModel();
$cartaoModel    = new CartaoCreditoModel();
$faturaModel    = new FaturaModel();
$transacaoModel = new TransacaoModel();
$planoCompraModel = new PlanoCompraModel();

$idConta = $contaModel->criar(['nome' => 'Conta Corrente Teste', 'saldo_inicial' => 1000], $idBeto);
checar('Conta criada com saldo inicial', $contaModel->saldoAtual($idConta) === 1000.0);

$idCategoria = $categoriaModel->criar(['nome' => 'Mercado', 'tipo' => 'despesa'], $idBeto);

$transacaoModel->criarManual([
    'conta_id' => $idConta, 'categoria_id' => $idCategoria, 'descricao' => 'Compra no pix',
    'valor' => 150, 'tipo' => 'despesa', 'modalidade' => 'pix',
    'data_fato_gerador' => date('Y-m-d'),
]);
checar('RN08/RN09: pix debita o saldo imediatamente (1000 - 150 = 850)', $contaModel->saldoAtual($idConta) === 850.0);

$idPlanoCompra = $planoCompraModel->criar([
    'nome' => 'Geladeira nova',
    'descricao' => 'Comprar geladeira 450L inox com pagamento em 10x',
    'imagem_url' => 'https://example.com/geladeira.jpg',
    'produto_url' => 'https://example.com/produto/geladeira',
    'valor_total' => 3200.00,
    'parcelas_previstas' => 10,
    'data_prevista_compra' => date('Y-m-d', strtotime('+30 days')),
], $idBeto);
checar('Plano de compra criado com id > 0', $idPlanoCompra > 0);
$planoSalvo = $planoCompraModel->buscarPorId($idPlanoCompra);
checar('Plano de compra tem status planejamento', $planoSalvo['status'] === 'planejamento');

$idCartao = $cartaoModel->criar(['nome' => 'Cartao Teste', 'dia_fechamento' => 5, 'dia_vencimento' => 15], $idConta);

$transacaoModel->criarManual([
    'conta_id' => $idConta, 'cartao_id' => $idCartao, 'categoria_id' => $idCategoria,
    'descricao' => 'Compra parcelada no credito', 'valor' => 300, 'tipo' => 'despesa',
    'modalidade' => 'credito', 'data_fato_gerador' => date('Y-m-d'),
]);
checar('RN09: transacao de credito NAO mexe no saldo da conta (continua 850)', $contaModel->saldoAtual($idConta) === 850.0);

$faturas = $faturaModel->listarPorCartao($idCartao);
checar('Fatura foi criada automaticamente para o cartao', count($faturas) === 1);
checar('RN09: valor da compra no credito foi para a fatura', (float) $faturas[0]['valor_total'] === 300.0);

$faturaModel->pagar((int) $faturas[0]['id']);
checar('RN09: ao pagar a fatura, o saldo da conta cai o valor total (850 - 300 = 550)', $contaModel->saldoAtual($idConta) === 550.0);
checar('Fatura marcada como paga', $faturaModel->buscarPorId((int) $faturas[0]['id'])['status'] === 'paga');

echo "\n========================================\n";
echo "6. RN10 -- duplicidade de sincronizacao via API\n";
echo "========================================\n";

$payload = [
    'descricao' => 'Supermercado via Open Finance', 'valor' => 89.9, 'tipo' => 'despesa',
    'modalidade' => 'debito', 'data_fato_gerador' => date('Y-m-d'), 'id_externo' => 'pluggy-tx-teste-001',
];

$primeiraSync = $transacaoModel->sincronizarViaApi($payload, $idConta);
checar('Primeira sincronizacao entra normalmente', $primeiraSync !== null && $primeiraSync > 0);

$segundaSync = $transacaoModel->sincronizarViaApi($payload, $idConta);
checar('RN10: segunda sincronizacao com o MESMO id_externo e recusada (retorna null)', $segundaSync === null);

$totalComEsseIdExterno = count(array_filter(
    $transacaoModel->listarPorConta($idConta),
    fn($t) => $t['id_externo'] === 'pluggy-tx-teste-001'
));
checar('RN10: so existe UMA linha no banco com esse id_externo', $totalComEsseIdExterno === 1);

echo "\n========================================\n";
echo "7. RN07 -- imutabilidade de transacao importada\n";
echo "========================================\n";

$transacaoApi = $transacaoModel->buscarPorId($primeiraSync);
$valorOriginal = (float) $transacaoApi['valor'];

$transacaoModel->atualizar($primeiraSync, [
    'valor' => 999999, 'data_fato_gerador' => '2000-01-01', 'categoria_id' => $idCategoria,
]);

$transacaoApiDepois = $transacaoModel->buscarPorId($primeiraSync);
checar('RN07: valor NAO mudou mesmo tentando forcar no update', (float) $transacaoApiDepois['valor'] === $valorOriginal);
checar('RN07: data_fato_gerador NAO mudou', $transacaoApiDepois['data_fato_gerador'] !== '2000-01-01');
checar('RN07: categoria_id FOI atualizada (campo liberado)', (int) $transacaoApiDepois['categoria_id'] === $idCategoria);

$excluiu = $transacaoModel->excluir($primeiraSync);
checar('RN07: exclusao de transacao importada e recusada', $excluiu === false);

echo "\n========================================\n";
echo "8. Tags\n";
echo "========================================\n";

$idTag1 = $tagModel->buscarOuCriar('essencial', $idBeto);
$idTag2 = $tagModel->buscarOuCriar('essencial', $idBeto); // mesma tag de novo
checar('buscarOuCriar e idempotente (mesmo id na segunda chamada)', $idTag1 === $idTag2);

$tagModel->vincularATransacao($primeiraSync, $idTag1);
$tagsDaTransacao = $tagModel->listarPorTransacao($primeiraSync);
checar('RN07: tag foi vinculada mesmo em transacao importada (campo liberado)', count($tagsDaTransacao) === 1);

echo "\n========================================\n";
echo "9. CompromissoModel -- RN01 (unicidade), RN02 (periodo), RN03 (notificacao)\n";
echo "========================================\n";

$compromissoModel = new \App\Model\CompromissoModel();

$rn02 = $compromissoModel->criar([
    'titulo' => 'Compromisso com periodo invertido', 'tipo' => 'lembrete',
    'data_inicio' => '2026-08-10 15:00:00', 'data_fim' => '2026-08-10 14:00:00',
], $idBeto);
checar('RN02: recusa quando data_fim <= data_inicio', $rn02['ok'] === false);

$reuniao1 = $compromissoModel->criar([
    'titulo' => 'Reuniao com cliente', 'tipo' => 'reuniao_presencial',
    'data_inicio' => '2026-08-10 14:00:00', 'data_fim' => '2026-08-10 15:00:00',
], $idBeto);
checar('Primeira reuniao presencial criada normalmente', $reuniao1['ok'] === true);

$reuniaoConflitante = $compromissoModel->criar([
    'titulo' => 'Outra reuniao no mesmo horario', 'tipo' => 'reuniao_presencial',
    'data_inicio' => '2026-08-10 14:30:00', 'data_fim' => '2026-08-10 15:30:00',
], $idBeto);
checar('RN01: recusa segunda reuniao presencial com horario sobreposto', $reuniaoConflitante['ok'] === false);

$lembreteMesmoHorario = $compromissoModel->criar([
    'titulo' => 'Lembrete no mesmo horario (tipo diferente)', 'tipo' => 'lembrete',
    'data_inicio' => '2026-08-10 14:30:00', 'data_fim' => '2026-08-10 15:30:00',
], $idBeto);
checar('RN01 so vale para reuniao_presencial -- lembrete no mesmo horario passa', $lembreteMesmoHorario['ok'] === true);

$reuniaoOutroHorario = $compromissoModel->criar([
    'titulo' => 'Reuniao em outro horario', 'tipo' => 'reuniao_presencial',
    'data_inicio' => '2026-08-10 16:00:00', 'data_fim' => '2026-08-10 17:00:00',
], $idBeto);
checar('Reuniao presencial em horario livre passa normalmente', $reuniaoOutroHorario['ok'] === true);

// RN03: compromisso vencendo em ~2h, com notificar_email=1, deve aparecer
// na lista de elegiveis para notificacao dentro da janela de 24h.
$idCompromissoNotificar = $compromissoModel->criar([
    'titulo' => 'Compromisso proximo (para RN03)', 'tipo' => 'lembrete',
    'data_inicio' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'data_fim'    => date('Y-m-d H:i:s', strtotime('+3 hours')),
    'notificar_email' => 1,
], $idBeto)['id'];

$elegiveis = $compromissoModel->listarParaNotificar('email', 24);
$estaNaLista = in_array($idCompromissoNotificar, array_column($elegiveis, 'id'));
checar('RN03: compromisso proximo com notificar_email=1 aparece na lista de elegiveis', $estaNaLista);

$compromissoModel->marcarNotificado($idCompromissoNotificar, 'email');
$elegiveisDepois = $compromissoModel->listarParaNotificar('email', 24);
$aindaNaLista = in_array($idCompromissoNotificar, array_column($elegiveisDepois, 'id'));
checar('RN03: apos marcarNotificado, o mesmo compromisso NAO aparece mais (evita spam)', !$aindaNaLista);

$compromissoModel->concluir($idCompromissoNotificar, $idBeto);
checar('Compromisso concluido muda de status', $compromissoModel->buscarPorId($idCompromissoNotificar)['status'] === 'concluido');

echo "\n========================================\n";
echo "RESULTADO FINAL: {$totalTestes} verificacoes, {$totalFalhas} falha(s)\n";
echo "========================================\n";

exit($totalFalhas > 0 ? 1 : 0);
