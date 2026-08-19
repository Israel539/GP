<!-- footer -->

<body class="d-flex flex-column min-vh-100">


    <footer class="bg-dark text-white mt-auto">
        <div class="text-center p-3">
            &copy; 2025 Copyright:
            <a class="text-white-50" href="#">GP.com</a>
        </div>
    </footer>

</body>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>

<?php include __DIR__ . '/chatSuporte.php'; ?>

<script>
    // Registra o Service Worker do PWA -- so em producao com HTTPS (ou em
    // localhost, que o navegador trata como "seguro" pra esse fim). Em
    // WAMP local acessado por http://gp/ isso falha silenciosamente e nao
    // atrapalha nada -- o site funciona normal, so sem as funcoes de PWA.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('<?= baseUrl() ?>sw.js').catch(function () {
                // Falha esperada em ambiente sem HTTPS (dev local) -- nao
                // precisa avisar o usuario, o site continua funcionando.
            });
        });
    }
</script>
</body>

</html>