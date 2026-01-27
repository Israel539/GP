<?php
if (! function_exists('mensagens')) {
    /**
     * mensagens
     *
     * @return string
     */
    
    function mensagens() : string
    {
        $msgSucesso = "";
        $msgError = "";
        $retHTML = "";

        if (isset($_SESSION['msgSucesso'])) {
            $msgSucesso = $_SESSION['msgSucesso'];
            // destroy a sessão 
            unset($_SESSION['msgSucesso']);
        }
        if (isset($_SESSION['msgError'])) {
            $msgError = $_SESSION['msgError'];
            // destroy a sessão
            unset($_SESSION['msgError']);
        }

        if (!empty($msgSucesso)) {
            $retHTML .= '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>' . $msgSucesso . '</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }

        if (!empty($msgError)) {
            $retHTML .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>' . $msgError . '</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }

        return $retHTML;
    }
}

if (! function_exists('header')) {
    /**
     * header
     *
     * @param string $titulo 
     * @param string $programa 
     * @return string
     */
    function header(
        string $titulo, 
        string $programa
        ) : string
    {
        $request = new \App\Library\Request();
        $action = $request->getAction();

        if ($action != "") {
            if ($action == "insert") {
                $subTitulo = "- Novo";
            } elseif ($action == "update") {
                $subTitulo = "- Alteração";
            } elseif ($action == "delete") {
                $subTitulo = "- Exclusão";
            } elseif ($action == "view") {
                $subTitulo = "- Visualização";
            }

            $btHTML = '<a href="/' . $programa . '" class="btn btn-secondary" title="Voltar">Voltar</a>';
        } else {
            $subTitulo = '';
            $btHTML = '<a href="/' . $programa . '/form/insert" class="btn btn-primary" title="Novo">Novo</a>';
        }

        $retHTML = '<div class="row">
                        <div class="col-10">
                            <h2>' . $titulo . $subTitulo . '</h2>
                        </div>
                        <div class="col-2 text-end">
                            ' . $btHTML . '
                        </div>
                    </div>

                    <hr />';
        
        $retHTML .= mensagens();

        return $retHTML;
    }
}

if (! function_exists('datatables')) {
    /**
     * datatables
     *
     * @param string $idTable 
     * @return string
     */
    function datatables($idTable)
    {
        return '
            <script src="https://cdn.datatables.net/v/bs5/dt-2.3.4/datatables.min.js" integrity="sha384-jVoHjtunWKmr2zpSki5PSXfFYRsTQQm1uk4wpf45zuYxast668XkB2fJL8PjloNc" crossorigin="anonymous"></script>
            <script>
                $(document).ready(function() {
                    $("#' . $idTable . '").DataTable({
                        language:   {
                                        "sEmptyTable":      "Nenhum registro encontrado",
                                        "sInfo":            "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                                        "sInfoEmpty":       "Mostrando 0 até 0 de 0 registros",
                                        "sInfoFiltered":    "(Filtrados de _MAX_ registros)",
                                        "sInfoPostFix":     "",
                                        "sInfoThousands":   ".",
                                        "sLengthMenu":      "_MENU_ resultados por página",
                                        "sLoadingRecords":  "Carregando...",
                                        "sProcessing":      "Processando...",
                                        "sZeroRecords":     "Nenhum registro encontrado",
                                        "sSearch":          "Pesquisar",
                                        "oPaginate": {
                                            "sNext":        "Próximo",
                                            "sPrevious":    "Anterior",
                                            "sFirst":       "Primeiro",
                                            "sLast":        "Último"
                                        },
                                        "oAria": {
                                            "sSortAscending":   ": Ordenar colunas de forma ascendente",
                                            "sSortDescending":  ": Ordenar colunas de forma descendente"
                                        }
                                    }
                    });
                });
            </script>';
    }
}