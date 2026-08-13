<?php
/**
 * Caixa de chat de suporte -- flutuante, arrastavel, aparece sozinha (sem
 * o usuario precisar fazer nada) em QUALQUER tela, tanto para o admin
 * quanto para o usuario alvo, sempre que existir uma sessao de suporte
 * ativa (Admin::suporteAcessar()) envolvendo quem esta logado. Some
 * sozinha quando a sessao expira ou e encerrada por qualquer um dos dois
 * lados.
 *
 * So e incluido quando $estaLogado (variavel ja definida por header.php,
 * compartilhada aqui porque header.php/footer.php sao incluidos na mesma
 * view, e include() em PHP nao cria escopo novo).
 */
?>
<?php if (!empty($estaLogado)): ?>
    <div id="chatSuporteWidget" class="chat-suporte-widget d-none">
        <div class="chat-suporte-cabecalho" id="chatSuporteCabecalho">
            <span><i class="bi bi-headset"></i> <span id="chatSuporteTitulo">Suporte</span></span>
            <div class="chat-suporte-cabecalho-acoes">
                <span id="chatSuporteTempo" class="chat-suporte-tempo"></span>
                <button type="button" class="chat-suporte-btn-icone" id="chatSuporteMinimizar" title="Minimizar/expandir">
                    <i class="bi bi-dash-lg"></i>
                </button>
            </div>
        </div>
        <div class="chat-suporte-corpo">
            <div class="chat-suporte-mensagens" id="chatSuporteMensagens"></div>
            <div class="chat-suporte-rodape">
                <form id="chatSuporteForm" class="d-flex gap-1">
                    <input type="text" id="chatSuporteInput" class="form-control form-control-sm"
                        placeholder="Digite uma mensagem..." autocomplete="off" maxlength="2000">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
                <button type="button" id="chatSuporteEncerrar" class="chat-suporte-encerrar">
                    Encerrar suporte
                </button>
            </div>
        </div>
    </div>

    <style>
        .chat-suporte-widget {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 320px;
            max-width: calc(100vw - 24px);
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.25);
            z-index: 1090;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .chat-suporte-cabecalho {
            background: #212529;
            color: #fff;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: move;
            user-select: none;
            font-size: 0.9rem;
        }

        .chat-suporte-cabecalho-acoes {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chat-suporte-tempo {
            font-size: 0.75rem;
            color: #adb5bd;
            font-variant-numeric: tabular-nums;
        }

        .chat-suporte-btn-icone {
            background: transparent;
            border: none;
            color: #fff;
            line-height: 1;
            padding: 2px 4px;
        }

        .chat-suporte-corpo {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .chat-suporte-widget.chat-suporte-minimizado .chat-suporte-corpo {
            display: none;
        }

        .chat-suporte-mensagens {
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .chat-suporte-bolha {
            max-width: 80%;
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            word-break: break-word;
        }

        .chat-suporte-bolha-nome {
            font-size: 0.68rem;
            font-weight: bold;
            opacity: 0.7;
            margin-bottom: 1px;
        }

        .chat-suporte-bolha-propria {
            align-self: flex-end;
            background: #0d6efd;
            color: #fff;
            border-bottom-right-radius: 2px;
        }

        .chat-suporte-bolha-outro {
            align-self: flex-start;
            background: #e9ecef;
            color: #212529;
            border-bottom-left-radius: 2px;
        }

        .chat-suporte-rodape {
            padding: 8px;
            border-top: 1px solid #dee2e6;
        }

        .chat-suporte-encerrar {
            background: transparent;
            border: none;
            color: #dc3545;
            font-size: 0.72rem;
            padding: 4px 0 0;
        }

        @media (max-width: 420px) {
            .chat-suporte-widget {
                right: 12px;
                left: 12px;
                width: auto;
            }
        }
    </style>

    <script>
        (function () {
            var CSRF_TOKEN_NAME = <?= json_encode(CSRF_TOKEN_NAME) ?>;
            var CSRF_TOKEN_VALUE = <?= json_encode(\App\Library\Csrf::getToken()) ?>;
            var USUARIO_ID_ATUAL = <?= (int) ($usuarioSessao['id'] ?? 0) ?>;

            var widget = document.getElementById('chatSuporteWidget');
            var cabecalho = document.getElementById('chatSuporteCabecalho');
            var mensagensEl = document.getElementById('chatSuporteMensagens');
            var form = document.getElementById('chatSuporteForm');
            var input = document.getElementById('chatSuporteInput');
            var tempoEl = document.getElementById('chatSuporteTempo');
            var tituloEl = document.getElementById('chatSuporteTitulo');
            var btnMinimizar = document.getElementById('chatSuporteMinimizar');
            var btnEncerrar = document.getElementById('chatSuporteEncerrar');

            var estado = { logId: null, ultimoId: 0, expiraEmTs: null };
            var timerMensagens = null;

            function postForm(url, campos) {
                var body = new URLSearchParams(campos || {});
                body.set(CSRF_TOKEN_NAME, CSRF_TOKEN_VALUE);
                return fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                }).then(function (r) { return r.json(); });
            }

            function getJson(url) {
                return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); });
            }

            function pararPollMensagens() {
                if (timerMensagens) {
                    clearInterval(timerMensagens);
                    timerMensagens = null;
                }
            }

            function iniciarPollMensagens() {
                pararPollMensagens();
                timerMensagens = setInterval(carregarMensagens, 4000);
            }

            function adicionarBolha(m) {
                var div = document.createElement('div');
                var propria = parseInt(m.autor_id, 10) === USUARIO_ID_ATUAL;
                div.className = 'chat-suporte-bolha ' + (propria ? 'chat-suporte-bolha-propria' : 'chat-suporte-bolha-outro');

                var nome = document.createElement('div');
                nome.className = 'chat-suporte-bolha-nome';
                nome.textContent = propria ? 'Você' : m.autor_nome;

                var texto = document.createElement('div');
                texto.textContent = m.mensagem;

                div.appendChild(nome);
                div.appendChild(texto);
                mensagensEl.appendChild(div);
                mensagensEl.scrollTop = mensagensEl.scrollHeight;
            }

            function carregarMensagens() {
                if (!estado.logId) return;
                getJson('/SuporteChat/mensagens/' + estado.logId + '?desde=' + estado.ultimoId)
                    .then(function (resp) {
                        if (!resp || !resp.mensagens) return;
                        resp.mensagens.forEach(function (m) {
                            adicionarBolha(m);
                            estado.ultimoId = m.id;
                        });
                    })
                    .catch(function () { /* silencioso -- tenta de novo no proximo ciclo */ });
            }

            function esconderWidget() {
                widget.classList.add('d-none');
                estado.logId = null;
                estado.ultimoId = 0;
                estado.expiraEmTs = null;
                mensagensEl.innerHTML = '';
                pararPollMensagens();
            }

            function verificarStatus() {
                getJson('/SuporteChat/status').then(function (resp) {
                    if (resp && resp.ativo) {
                        if (estado.logId !== resp.log_id) {
                            estado.logId = resp.log_id;
                            estado.ultimoId = 0;
                            mensagensEl.innerHTML = '';
                            carregarMensagens();
                            iniciarPollMensagens();
                        }
                        estado.expiraEmTs = resp.expira_em_ts;
                        tituloEl.textContent = resp.papel === 'admin'
                            ? ('Suporte com ' + resp.outro_nome)
                            : 'Suporte técnico';
                        widget.classList.remove('d-none');
                    } else if (estado.logId) {
                        esconderWidget();
                    }
                }).catch(function () { /* silencioso -- tenta de novo no proximo ciclo */ });
            }

            function atualizarContador() {
                if (!estado.expiraEmTs) {
                    tempoEl.textContent = '';
                    return;
                }
                var restante = estado.expiraEmTs - Math.floor(Date.now() / 1000);
                if (restante <= 0) {
                    esconderWidget();
                    return;
                }
                var min = Math.floor(restante / 60);
                var seg = String(restante % 60).padStart(2, '0');
                tempoEl.textContent = min + ':' + seg;
            }

            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var texto = input.value.trim();
                if (!texto || !estado.logId) return;
                input.value = '';
                postForm('/SuporteChat/enviar/' + estado.logId, { mensagem: texto }).then(function (resp) {
                    if (resp && resp.ok && resp.mensagem) {
                        adicionarBolha(resp.mensagem);
                        estado.ultimoId = resp.mensagem.id;
                    }
                });
            });

            btnEncerrar.addEventListener('click', function () {
                if (!estado.logId) return;
                if (!confirm('Encerrar o suporte agora? A conversa vai desaparecer para os dois lados.')) return;
                postForm('/SuporteChat/encerrar/' + estado.logId).then(function () {
                    esconderWidget();
                });
            });

            btnMinimizar.addEventListener('click', function () {
                widget.classList.toggle('chat-suporte-minimizado');
            });

            // Arrastar a caixa pela tela (mouse + touch, sem depender de lib externa)
            (function tornarArrastavel() {
                var arrastando = false;
                var offsetX = 0;
                var offsetY = 0;

                function comecar(clientX, clientY) {
                    arrastando = true;
                    var rect = widget.getBoundingClientRect();
                    offsetX = clientX - rect.left;
                    offsetY = clientY - rect.top;
                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';
                    widget.style.left = rect.left + 'px';
                    widget.style.top = rect.top + 'px';
                }

                function mover(clientX, clientY) {
                    if (!arrastando) return;
                    var novoLeft = clientX - offsetX;
                    var novoTop = clientY - offsetY;
                    novoLeft = Math.max(0, Math.min(window.innerWidth - widget.offsetWidth, novoLeft));
                    novoTop = Math.max(0, Math.min(window.innerHeight - widget.offsetHeight, novoTop));
                    widget.style.left = novoLeft + 'px';
                    widget.style.top = novoTop + 'px';
                }

                function soltar() {
                    arrastando = false;
                }

                cabecalho.addEventListener('mousedown', function (ev) {
                    comecar(ev.clientX, ev.clientY);
                });
                document.addEventListener('mousemove', function (ev) {
                    mover(ev.clientX, ev.clientY);
                });
                document.addEventListener('mouseup', soltar);

                cabecalho.addEventListener('touchstart', function (ev) {
                    var t = ev.touches[0];
                    comecar(t.clientX, t.clientY);
                }, { passive: true });
                document.addEventListener('touchmove', function (ev) {
                    if (!arrastando) return;
                    var t = ev.touches[0];
                    mover(t.clientX, t.clientY);
                }, { passive: true });
                document.addEventListener('touchend', soltar);
            })();

            verificarStatus();
            // Baseline de 15s: suporte ativo e raro (a maioria do tempo,
            // ninguem esta em atendimento), entao nao faz sentido consultar
            // rapido o tempo todo -- so as MENSAGENS (iniciarPollMensagens,
            // 4s) precisam ser rapidas, e essas so comecam a rodar quando
            // ja existe uma sessao ativa de verdade.
            setInterval(verificarStatus, 15000);
            setInterval(atualizarContador, 1000);
        })();
    </script>
<?php endif; ?>
