<?php
// mesmo esquema do cadastro: os dados chegam criptografados
// o servidor descriptografa, ai verifica o usuario no banco
header('Content-Type: application/json');
require_once 'config.php';

// le o corpo da requisicao com os dados cifrados
$body = json_decode(file_get_contents('php://input'), true);

// passo 1: pega a chave privada RSA pra descriptografar a chave AES
$chave_privada = file_get_contents(KEYS_DIR . 'private.pem');
if (!$chave_privada) {
    echo json_encode(['erro' => 'Chave RSA nao encontrada. Recarregue a pagina e tente novamente.']);
    exit;
}

// descriptografa a chave AES usando a chave privada RSA do servidor
openssl_private_decrypt(
    base64_decode($body['chave_criptografada']),
    $chave_aes,
    $chave_privada,
    OPENSSL_PKCS1_OAEP_PADDING
);

// passo 2: usa a chave AES pra descriptografar o email e a senha
$dados_json = openssl_decrypt(
    base64_decode($body['dados_criptografados']),
    'AES-256-CBC',
    $chave_aes,
    OPENSSL_RAW_DATA,
    base64_decode($body['iv'])
);

if (!$dados_json) {
    echo json_encode(['erro' => 'Falha na descriptografia. Recarregue a pagina e tente novamente.']);
    exit;
}

// agora tenho o email e a senha que o usuario digitou
$form = json_decode($dados_json, true);

// conecta no banco
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['erro' => 'Banco de dados indisponivel. Verifique se o MySQL esta rodando.']);
    exit;
}

// busca o usuario pelo email usando prepared statement (evita SQL injection)
$st = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
if (!$st) {
    echo json_encode(['erro' => 'Erro interno no servidor.']);
    exit;
}

$st->bind_param("s", $form['email']);
$st->execute();
$usuario = $st->get_result()->fetch_assoc();

// verifica se o usuario existe e se a senha bate com o hash salvo no banco
// o password_verify compara a senha digitada com o hash bcrypt, nao precisa descriptografar
if (!$usuario || !password_verify($form['senha'], $usuario['senha'])) {
    echo json_encode(['erro' => 'Email ou senha incorretos']);
    exit;
}

// login ok, manda os dados do usuario pro javascript (sem a senha, claro)
echo json_encode([
    'sucesso' => true,
    'usuario' => ['id' => $usuario['id'], 'nome' => $usuario['nome'], 'email' => $form['email']]
]);
