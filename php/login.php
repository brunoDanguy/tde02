<?php
header('Content-Type: application/json');
require_once 'config.php';

// recebe os dados criptografados enviados pelo navegador
$body = json_decode(file_get_contents('php://input'), true);

// passo 1: RSA descriptografa a chave AES usando a chave privada do servidor
$chave_privada = file_get_contents(KEYS_DIR . 'private.pem');
openssl_private_decrypt(
    base64_decode($body['chave_criptografada']),
    $chave_aes,
    $chave_privada,
    OPENSSL_PKCS1_OAEP_PADDING
);

// passo 2: AES descriptografa os dados do formulario usando a chave AES
$dados_json = openssl_decrypt(
    base64_decode($body['dados_criptografados']),
    'AES-256-CBC',
    $chave_aes,
    OPENSSL_RAW_DATA,
    base64_decode($body['iv'])
);

$form = json_decode($dados_json, true);

// busca o usuario no banco pelo email
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$st = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
$st->bind_param("s", $form['email']);
$st->execute();
$usuario = $st->get_result()->fetch_assoc();

// verifica se o usuario existe e se a senha confere com o hash
if (!$usuario || !password_verify($form['senha'], $usuario['senha'])) {
    echo json_encode(['erro' => 'Email ou senha incorretos']);
    exit;
}

echo json_encode([
    'sucesso' => true,
    'usuario' => ['id' => $usuario['id'], 'nome' => $usuario['nome'], 'email' => $form['email']]
]);
