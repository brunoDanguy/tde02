// esse arquivo faz toda a criptografia antes de enviar qualquer dado pro servidor
// uso criptografia hibrida: RSA + AES juntos
// por que hibrida? RSA e lento pra dados grandes, AES e rapido mas precisa trocar a chave com seguranca
// entao: uso AES pra cifrar os dados e RSA pra cifrar a chave do AES

// converte ArrayBuffer pra base64 pra poder colocar no JSON
// o JSON so aceita texto, nao bytes brutos
function bufferParaBase64(buffer) {
    var bytes = new Uint8Array(buffer);
    var binario = '';
    for (var i = 0; i < bytes.length; i++) {
        binario += String.fromCharCode(bytes[i]);
    }
    return btoa(binario);
}

// a chave publica RSA vem do servidor como texto PEM (aquele formato com -----BEGIN PUBLIC KEY-----)
// preciso converter pra bytes pra usar na API de criptografia do navegador
function pemParaBuffer(pem) {
    // remove o cabecalho, rodape e quebras de linha, fica so o base64 puro
    var base64 = pem.replace(/-----[^-]+-----/g, '').replace(/\s/g, '');
    var binario = atob(base64);
    var bytes = new Uint8Array(binario.length);
    for (var i = 0; i < binario.length; i++) {
        bytes[i] = binario.charCodeAt(i);
    }
    return bytes.buffer;
}

// busca a chave publica RSA do servidor
// essa funcao e chamada toda vez antes de criptografar
// a chave publica pode ser compartilhada, nao tem problema
async function obterChavePublica() {
    var resposta = await fetch('php/get_public_key.php');
    var dados = await resposta.json();
    return dados.chave_publica;
}

// funcao principal: recebe os dados do formulario e retorna tudo criptografado
async function criptografarHibrido(dados) {

    // 1. pega a chave publica RSA do servidor
    var pemPublico = await obterChavePublica();

    // 2. importa a chave publica no formato que a Web Crypto API entende
    // RSA-OAEP com SHA-1 pra ser compativel com o openssl do PHP
    var chaveRSA = await crypto.subtle.importKey(
        'spki',
        pemParaBuffer(pemPublico),
        { name: 'RSA-OAEP', hash: 'SHA-1' },
        false,
        ['encrypt']
    );

    // 3. gera uma chave AES de 256 bits totalmente aleatoria
    // essa chave e diferente em cada envio de formulario
    var chaveAES = await crypto.subtle.generateKey(
        { name: 'AES-CBC', length: 256 },
        true,    // precisa ser true pra poder exportar depois
        ['encrypt']
    );

    // 4. gera o IV (vetor de inicializacao) aleatorio de 16 bytes
    // o IV garante que se eu cifrar o mesmo texto duas vezes, o resultado e diferente
    var iv = crypto.getRandomValues(new Uint8Array(16));

    // 5. converte os dados do formulario pra JSON e depois pra bytes, ai cifra com AES
    var dadosCodificados = new TextEncoder().encode(JSON.stringify(dados));
    var dadosCifrados = await crypto.subtle.encrypt(
        { name: 'AES-CBC', iv: iv },
        chaveAES,
        dadosCodificados
    );

    // 6. exporta a chave AES como bytes brutos pra poder cifrar ela com RSA
    var chaveAESBruta = await crypto.subtle.exportKey('raw', chaveAES);

    // 7. cifra a chave AES com RSA usando a chave publica do servidor
    // so quem tem a chave privada (o servidor) consegue abrir
    var chaveCifrada = await crypto.subtle.encrypt(
        { name: 'RSA-OAEP' },
        chaveRSA,
        chaveAESBruta
    );

    // retorna os tres campos em base64 pra mandar via JSON
    // mostrar isso no network do devtools na hora da apresentacao
    return {
        dados_criptografados: bufferParaBase64(dadosCifrados),   // formulario cifrado com AES
        chave_criptografada:  bufferParaBase64(chaveCifrada),    // chave AES cifrada com RSA
        iv:                   bufferParaBase64(iv)               // vetor de inicializacao do AES
    };
}
