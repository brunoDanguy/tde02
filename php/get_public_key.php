<?php
// esse arquivo e chamado pelo javascript antes de criptografar qualquer coisa
// o navegador precisa da chave publica RSA pra poder cifrar a chave AES
// a chave privada nunca sai do servidor, so a publica e compartilhada
header('Content-Type: application/json');
require_once 'config.php';

// le o arquivo da chave publica e manda pro navegador em formato JSON
echo json_encode(['chave_publica' => file_get_contents(KEYS_DIR . 'public.pem')]);
