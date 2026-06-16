<?php
// esse arquivo recebe os dados do formulario de cadastro ja criptografados
// nenhum dado chega em texto puro aqui, tudo vem cifrado pelo javascript
header('Content-Type: application/json');
require_once 'config.php';

// le o corpo da requisicao POST que vem do javascript
// o json tem tres campos: dados_criptografados, chave_criptografada e iv
$body = json_decode(file_get_contents('php://input'), true);

// passo 1 da descriptografia: pega a chave privada RSA do servidor
// essa chave nunca foi pro navegador, so existe aqui no servidor
$chave_privada = file_get_contents(KEYS_DIR . 'private.pem');
if (!$chave_privada) {
    // se a chave nao existe ainda, pede pra recarregar (o config.php vai gerar)
    echo json_encode(['erro' => 'Chave RSA nao encontrada. Recarregue a pagina e tente novamente.']);
    exit;
}

// usa a chave privada RSA pra descriptografar a chave AES
// so funciona com a chave privada correta, por isso RSA e seguro pra isso
// o resultado fica na variavel $chave_aes
openssl_private_decrypt(
    base64_decode($body['chave_criptografada']),
    $chave_aes,
    $chave_privada,
    OPENSSL_PKCS1_OAEP_PADDING  // padding usado no javascript tambem, tem que combinar
);

// passo 2: usa a chave AES (que acabei de descriptografar) pra abrir os dados do formulario
// o iv e necessario pro AES-CBC, foi gerado aleatoriamente no javascript
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

// agora sim tenho os dados em texto: nome, email e senha
$form = json_decode($dados_json, true);

// conecta no banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['erro' => 'Banco de dados indisponivel. Verifique se o MySQL esta rodando.']);
    exit;
}

// gera o hash bcrypt da senha antes de salvar
// nunca salvo a senha em texto puro no banco, so o hash
// se alguem invadir o banco, nao consegue recuperar a senha original
$hash = password_hash($form['senha'], PASSWORD_BCRYPT);

// uso prepared statement pra evitar SQL injection
$st = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
if (!$st) {
    echo json_encode(['erro' => 'Erro interno no servidor.']);
    exit;
}

// substitui os ? pelos valores reais de forma segura
$st->bind_param("sss", $form['nome'], $form['email'], $hash);

if ($st->execute()) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Cadastro realizado!']);
} else {
    // se o execute falhou provavelmente e email duplicado (campo UNIQUE no banco)
    echo json_encode(['erro' => 'Email ja cadastrado']);
}
