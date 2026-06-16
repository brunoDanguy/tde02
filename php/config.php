<?php
// escondo os erros do PHP pra nao aparecer HTML misturado com o JSON que retorno
error_reporting(0);
ini_set('display_errors', 0);

// configuracoes do banco de dados
// lembrar de mencionar que a senha fica so no servidor, nunca vai pro navegador
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '0110@@br');
define('DB_NAME', 'tde02');

// pasta onde vao ficar as chaves RSA geradas
// essa pasta ta no .gitignore, ou seja, nunca vai pro github (importante falar isso)
define('KEYS_DIR', dirname(__DIR__) . '/keys/');

// desativo excecoes do mysqli pra poder tratar os erros manualmente
mysqli_report(MYSQLI_REPORT_OFF);

// tento conectar no mysql sem banco especifico primeiro
// preciso criar o banco antes de selecionar ele
$c = new mysqli(DB_HOST, DB_USER, DB_PASS, '', 3306);
if (!$c->connect_error) {

    // cria o banco se nao existir ainda (primeira vez rodando)
    $c->query("CREATE DATABASE IF NOT EXISTS tde02 CHARACTER SET utf8mb4");
    $c->select_db('tde02');

    // cria a tabela de usuarios se nao existir
    // a senha aqui nunca vai ser salva em texto puro, so o hash bcrypt
    $c->query("CREATE TABLE IF NOT EXISTS usuarios (
        id    INT AUTO_INCREMENT PRIMARY KEY,
        nome  VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL
    )");

    $c->close();
}

// gera as chaves RSA na primeira vez que o sistema roda
// chave privada fica so no servidor, chave publica vai pro navegador
if (!file_exists(KEYS_DIR . 'private.pem')) {

    mkdir(KEYS_DIR, 0755, true);

    // no windows o openssl nao acha o arquivo de configuracao sozinho
    // precisa passar o caminho manualmente, se nao a funcao retorna false
    $res = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'config' => 'C:/xampp/apache/conf/openssl.cnf'
    ]);

    // exporta a chave privada pra uma variavel
    openssl_pkey_export($res, $priv, null, ['config' => 'C:/xampp/apache/conf/openssl.cnf']);

    // pega os detalhes, la dentro tem a chave publica
    $det = openssl_pkey_get_details($res);

    // salva as duas chaves em arquivos .pem
    file_put_contents(KEYS_DIR . 'private.pem', $priv);
    file_put_contents(KEYS_DIR . 'public.pem',  $det['key']);
}
