<?php
// Cria o usuario administrador inicial do sistema.
// Uso (rodar UMA vez, na primeira instalacao):
//   php scripts/criar_super_usuario.php
//
// O script pede nome, e-mail e senha interativamente -- nada fica
// hardcoded aqui. Se ja existir um admin com o e-mail informado, ele avisa
// e nao faz nada (idempotente).

// Trava de seguranca: se por engano esse arquivo for exposto num servidor
// web mal configurado, isso impede que ele rode via HTTP.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Este script so pode ser executado via linha de comando (CLI).\n");
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/config.php';

use App\Model\UsuarioModel;

function lerEntrada(string $pergunta): string
{
    echo $pergunta;
    return trim((string) fgets(STDIN));
}

function lerSenha(string $pergunta): string
{
    echo $pergunta;

    // Tenta esconder o que e digitado
    if (stripos(PHP_OS, 'WIN') === false) {
        exec('stty -echo');
        $senha = trim((string) fgets(STDIN));
        exec('stty echo');
        echo "\n";
        return $senha;
    }

    return trim((string) fgets(STDIN));
}

echo "=== Criacao do usuario administrador (Projeto GP) ===\n\n";

$nome = lerEntrada('Nome completo: ');
$email = lerEntrada('E-mail de login: ');

if ($nome === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("\nNome ou e-mail invalido. Abortando.\n");
}

$senha = lerSenha('Senha (minimo 6 caracteres): ');
$confirmacao = lerSenha('Confirme a senha: ');

if (strlen($senha) < 6) {
    exit("\nA senha precisa ter pelo menos 6 caracteres. Abortando.\n");
}

if ($senha !== $confirmacao) {
    exit("\nAs senhas nao conferem. Abortando.\n");
}

$usuarioModel = new UsuarioModel();

$existente = $usuarioModel->getUserEmail($email);

if (count($existente) > 0) {
    exit("\nJa existe um usuario cadastrado com esse e-mail. Nada foi feito.\n");
}

$id = $usuarioModel->insert([
    'nome'           => $nome,
    'email'          => $email,
    'senha'          => $senha,
    'nivel'          => UsuarioModel::NIVEL_ADMIN,
    'statusRegistro' => 1,
]);

if ($id > 0) {
    echo "\nUsuario administrador criado com sucesso (id {$id}).\n";
} else {
    exit("\nFalha ao criar o usuario. Confira a conexao com o banco de dados.\n");
}
