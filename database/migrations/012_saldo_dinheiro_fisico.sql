-- Migration 012: saldo em dinheiro fisico (opcional, editavel pelo usuario)
--
-- Widget no topo de /Conta que mostra Dinheiro fisico + Saldo em conta
-- (RN08, calculado) + Total. So aparece se o usuario ativar a checkbox
-- (exibir_saldo_dinheiro) -- por padrao fica desligado, e a tela de Contas
-- continua exatamente como sempre foi.
--
-- 'saldo_dinheiro' e um valor a parte, editavel a mao de proposito -- NAO
-- e o saldo de uma conta (que continua 100% calculado, RN08 intacta). E
-- dinheiro fisico que a pessoa carrega, sem nenhuma transacao do sistema
-- por tras -- por isso um usuario so tem UM valor (nao e por conta).

ALTER TABLE usuarios
    ADD COLUMN saldo_dinheiro DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'Dinheiro fisico, editado a mao pelo usuario -- nao e calculado, ao contrario do saldo de conta (RN08)',
    ADD COLUMN exibir_saldo_dinheiro TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Preferencia do usuario: mostrar ou nao o widget de dinheiro fisico + total em /Conta';
