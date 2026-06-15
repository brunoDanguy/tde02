<?php
header('Content-Type: application/json');
require_once 'config.php';

// retorna a chave publica RSA pro navegador
echo json_encode(['chave_publica' => file_get_contents(KEYS_DIR . 'public.pem')]);
