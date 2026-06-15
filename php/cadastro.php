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

// salva o usuario no banco com a senha em hash
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$hash = password_hash($form['senha'], PASSWORD_BCRYPT);

$st = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
$st->bind_param("sss", $form['nome'], $form['email'], $hash);

if ($st->execute()) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Cadastro realizado!']);
} else {
    echo json_encode(['erro' => 'Email ja cadastrado']);
}
