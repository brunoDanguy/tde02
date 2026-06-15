<?php
require_once 'config.php';

// Cria banco e tabela
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
if ($conn->connect_error) die('Erro MySQL: ' . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);
$conn->query("CREATE TABLE IF NOT EXISTS usuarios (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL,
    email     VARCHAR(150) NOT NULL UNIQUE,
    senha     VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->close();
echo "Banco e tabela criados.<br>";

// Gera chaves RSA
if (!is_dir(KEYS_DIR)) mkdir(KEYS_DIR, 0755, true);
if (!file_exists(KEYS_DIR . 'private.pem')) {
    $res = openssl_pkey_new(['digest_alg' => 'sha256', 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($res, $priv);
    $det = openssl_pkey_get_details($res);
    file_put_contents(KEYS_DIR . 'private.pem', $priv);
    file_put_contents(KEYS_DIR . 'public.pem',  $det['key']);
    echo "Chaves RSA geradas.<br>";
} else {
    echo "Chaves RSA ja existem.<br>";
}

echo "<br><strong>Setup OK!</strong> <a href='../index.html'>Ir para o sistema</a>";
