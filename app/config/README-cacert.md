# Erro "SSL certificate problem: unable to get local issuer certificate" (curl_errno 60)

Isso e um problema classico de Windows/WAMP: o cURL do PHP nao vem com o
"bundle" de certificados raiz que o Windows usa nativamente, entao toda
chamada HTTPS feita via `curl_exec()` falha com esse erro -- mesmo o site
sendo confiavel e abrindo normal no navegador.

## Opcao A (recomendada): colocar o cacert.pem dentro do projeto

O `FeriadoService.php` ja procura automaticamente por um arquivo em
`app/config/cacert.pem`. Se ele existir, usa ele; se nao existir, usa o
comportamento padrao do PHP (que e o que esta falhando agora).

1. Baixe o arquivo em: https://curl.se/ca/cacert.pem
   (e o bundle oficial de certificados raiz, mantido pelo projeto curl/Mozilla)
2. Salve o arquivo baixado exatamente como `app/config/cacert.pem`
   (mesma pasta deste README)
3. Pronto -- nao precisa reiniciar o WAMP nem mexer no php.ini. Na proxima
   chamada, o `FeriadoService` ja vai usar esse arquivo.

Esse arquivo fica de fora do git (adicionar `app/config/cacert.pem` ao
.gitignore, se quiser) porque e um arquivo grande (~200KB) que muda de tempos
em tempos -- cada dev que rodar o projeto localmente no Windows baixa o seu.

## Opcao B: configurar globalmente no php.ini do WAMP

Se preferir resolver pra qualquer chamada HTTPS do PHP (nao so a deste
projeto), da pra configurar direto no php.ini:

1. Baixe o `cacert.pem` (mesmo link acima) e salve em algum lugar fixo, ex:
   `C:\wamp64\bin\php\php8.4.0\extras\ssl\cacert.pem`
2. Abra o php.ini correto -- CUIDADO: o WAMP tem um php.ini pra Apache e
   outro pra CLI (linha de comando), sao arquivos diferentes. Pra achar o
   do Apache, veja no icone do WAMP na bandeja: PHP > php.ini. Pra achar o
   da CLI, rode `php --ini` no terminal (foi o que apareceu no seu teste).
3. Procure a secao `[curl]` e defina:
   ```
   curl.cainfo = "C:\wamp64\bin\php\php8.4.0\extras\ssl\cacert.pem"
   ```
4. Se editou o php.ini do Apache, reinicie os servicos do WAMP (clique
   direito no icone da bandeja > Restart All Services).

## O que NAO fazer

Nao desabilite a verificacao de certificado (`CURLOPT_SSL_VERIFYPEER =>
false`) so pra "resolver rapido". Isso faz o PHP aceitar qualquer
certificado, inclusive um forjado por alguem interceptando a conexao --
deixa de fazer sentido usar HTTPS. O problema real e so a falta do bundle
de certificados, e as duas opcoes acima resolvem isso de verdade.
