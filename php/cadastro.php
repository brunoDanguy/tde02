<?php
header('Content-Type: application/json');
require_once 'config.php';

// recebe os dados criptografados enviados pelo navegador
$body = json_decode(file_get_contents('php://input'), true);

// passo 1: RSA descriptografa a chave AES usando a chave privada do servidor
$chave_privada = file_get_contents(KEYS_DIR . 'private.pem');
if (!$chave_privada) {
    echo json_encode(['erro' => 'Chave RSA nao encontrada. Recarregue a pagina e tente novamente.']);
    exit;
}

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

if (!$dados_json) {
    echo json_encode(['erro' => 'Falha na descriptografia. Recarregue a pagina e tente novamente.']);
    exit;
}

$form = json_decode($dados_json, true);

// salva o usuario no banco com a senha em hash
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['erro' => 'Banco de dados indisponivel. Verifique se o MySQL esta rodando.']);
    exit;
}

$hash = password_hash($form['senha'], PASSWORD_BCRYPT);
$st = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
if (!$st) {
    echo json_encode(['erro' => 'Erro interno no servidor.']);
    exit;
}
$st->bind_param("sss", $form['nome'], $form['email'], $hash);

if ($st->execute()) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Cadastro realizado!']);
} else {
    echo json_encode(['erro' => 'Email ja cadastrado']);
}
