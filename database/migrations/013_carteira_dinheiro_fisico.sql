-- Migration 013: "Dinheiro Fisico" vira uma conta de verdade (tipo
-- 'carteira', que ja existia no ENUM de contas.tipo mas nunca era usada),
-- com saldo calculado automaticamente a partir das transacoes -- em vez de
-- um numero editado a mao (migracao 012).
--
-- Motivo: o numero editado a mao (usuarios.saldo_dinheiro) nunca reagia a
-- lancamentos com modalidade = 'dinheiro'. Como toda transacao TINHA que
-- estar presa a uma conta bancaria, lancar uma despesa em dinheiro descontava
-- da conta bancaria escolhida na tela -- nunca do dinheiro fisico. Agora
-- "Dinheiro Fisico" funciona exatamente como qualquer outra conta (RN08):
-- saldo = saldo_inicial + receitas - despesas confirmadas.

-- Cria 1 conta tipo='carteira' pra cada usuario que ainda nao tem uma,
-- migrando o saldo_dinheiro atual dele pro saldo_inicial dessa conta nova
-- (assim ninguem perde o valor que ja tinha guardado ali).
INSERT INTO contas (usuario_id, nome, tipo, saldo_inicial)
SELECT u.id, 'Dinheiro Físico', 'carteira', u.saldo_dinheiro
FROM usuarios u
WHERE NOT EXISTS (
    SELECT 1 FROM contas c WHERE c.usuario_id = u.id AND c.tipo = 'carteira'
);

-- usuarios.saldo_dinheiro fica sem uso a partir de agora (o valor real mora
-- na conta 'carteira', calculado). Nao apagamos a coluna aqui de proposito
-- -- so pra manter o historico/permitir conferencia manual se precisar,
-- mas o codigo PHP para de ler/escrever nela.
-- usuarios.exibir_saldo_dinheiro continua em uso normalmente (so controla
-- se o widget aparece na tela, nunca guardou o saldo em si).

-- A VIEW nao e mais usada em lugar nenhum do codigo (o calculo de saldo
-- virou uma consulta comum dentro do ContaModel) -- alguns provedores de
-- hospedagem compartilhada nao liberam CREATE VIEW pra contas gratuitas,
-- entao tirar essa dependencia deixa o projeto rodavel em qualquer
-- hospedagem PHP+MySQL/MariaDB padrao.
DROP VIEW IF EXISTS vw_saldo_contas;
