<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tde02');
define('KEYS_DIR', dirname(__DIR__) . '/keys/');

// cria o banco e a tabela se nao existirem
$c = new mysqli(DB_HOST, DB_USER, DB_PASS, '', 3306);
$c->query("CREATE DATABASE IF NOT EXISTS tde02 CHARACTER SET utf8mb4");
$c->select_db('tde02');
$c->query("CREATE TABLE IF NOT EXISTS usuarios (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    nome  VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
)");
$c->close();

// gera as chaves RSA se nao existirem
if (!file_exists(KEYS_DIR . 'private.pem')) {
    mkdir(KEYS_DIR, 0755, true);
    $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($res, $priv);
    $det = openssl_pkey_get_details($res);
    file_put_contents(KEYS_DIR . 'private.pem', $priv);
    file_put_contents(KEYS_DIR . 'public.pem',  $det['key']);
}
