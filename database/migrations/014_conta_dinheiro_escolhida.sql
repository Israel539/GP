-- Migration 014: troca a ideia de uma conta 'carteira' criada
-- AUTOMATICAMENTE (migracao 013) por deixar o USUARIO escolher qual conta
-- que ele ja tem representa o "Dinheiro Fisico" dele.
--
-- Motivo: depois de testar, ficou incomodo ter o dinheiro fisico numa
-- conta separada, com lista/saldo proprios -- a pessoa queria continuar
-- vendo tudo junto na mesma conta que ja usa (ex: a conta do banco X),
-- so que lancamentos em modalidade 'dinheiro' tambem descontando dela.

-- Marca qual conta (das que a pessoa ja tem) recebe as transacoes de
-- modalidade='dinheiro' automaticamente. So uma conta por usuario pode
-- estar marcada (aplicacao garante isso, nao tem como fazer via
-- UNIQUE INDEX parcial de forma portavel entre MySQL e MariaDB).
ALTER TABLE contas
    ADD COLUMN eh_conta_dinheiro TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo;

-- Nao promove nenhuma conta automaticamente -- e uma escolha explicita,
-- feita na tela de Contas. Enquanto a pessoa nao escolher, lancar em
-- modalidade 'dinheiro' pede pra configurar antes.

-- As contas tipo='carteira' criadas pela migracao 013 (se a pessoa ja
-- tinha testado essa versao) CONTINUAM existindo, agora como contas
-- comuns na lista -- nao sao mais escondidas nem tratadas de forma
-- especial. Se sobrou uma "Dinheiro Físico" vazia (sem transacao
-- nenhuma) so de teste, pode excluir pela tela normal de Contas.
