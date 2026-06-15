<?php
error_reporting(0);
ini_set('display_errors', 0);

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '0110@@br');
define('DB_NAME', 'tde02');
define('KEYS_DIR', dirname(__DIR__) . '/keys/');

// cria o banco e a tabela se nao existirem
mysqli_report(MYSQLI_REPORT_OFF);
$c = new mysqli(DB_HOST, DB_USER, DB_PASS, '', 3306);
if (!$c->connect_error) {
    $c->query("CREATE DATABASE IF NOT EXISTS tde02 CHARACTER SET utf8mb4");
    $c->select_db('tde02');
    $c->query("CREATE TABLE IF NOT EXISTS usuarios (
        id    INT AUTO_INCREMENT PRIMARY KEY,
        nome  VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL
    )");
    $c->close();
}

// gera as chaves RSA se nao existirem
if (!file_exists(KEYS_DIR . 'private.pem')) {
    mkdir(KEYS_DIR, 0755, true);
    // no windows o openssl precisa do caminho do .cnf explicito
    $res = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'config' => 'C:/xampp/apache/conf/openssl.cnf'
    ]);
    openssl_pkey_export($res, $priv, null, ['config' => 'C:/xampp/apache/conf/openssl.cnf']);
    $det = openssl_pkey_get_details($res);
    file_put_contents(KEYS_DIR . 'private.pem', $priv);
    file_put_contents(KEYS_DIR . 'public.pem',  $det['key']);
}
