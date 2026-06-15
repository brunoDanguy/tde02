// esse arquivo faz a criptografia hibrida antes de enviar os dados pro servidor
// hibrida = usa RSA e AES juntos
// RSA sozinho e lento pra dados grandes, entao a gente usa AES pra cifrar os dados
// e usa o RSA so pra cifrar a chave do AES

// converte um ArrayBuffer pra base64 pra poder mandar no JSON
function bufferParaBase64(buffer) {
    var bytes = new Uint8Array(buffer);
    var binario = '';
    for (var i = 0; i < bytes.length; i++) {
        binario += String.fromCharCode(bytes[i]);
    }
    return btoa(binario);
}

// a chave publica RSA vem do servidor no formato PEM (texto)
// precisa converter pra binario pra usar na Web Crypto API
function pemParaBuffer(pem) {
    var base64 = pem.replace(/-----[^-]+-----/g, '').replace(/\s/g, '');
    var binario = atob(base64);
    var bytes = new Uint8Array(binario.length);
    for (var i = 0; i < binario.length; i++) {
        bytes[i] = binario.charCodeAt(i);
    }
    return bytes.buffer;
}

// busca a chave publica RSA que ta salva no servidor
async function obterChavePublica() {
    var resposta = await fetch('php/get_public_key.php');
    var dados = await resposta.json();
    return dados.chave_publica;
}

// funcao principal que criptografa os dados do formulario
async function criptografarHibrido(dados) {

    // pega a chave publica RSA do servidor
    var pemPublico = await obterChavePublica();

    // importa a chave publica pra usar com RSA-OAEP
    var chaveRSA = await crypto.subtle.importKey(
        'spki',
        pemParaBuffer(pemPublico),
        { name: 'RSA-OAEP', hash: 'SHA-1' },
        false,
        ['encrypt']
    );

    // gera uma chave AES de 256 bits aleatoria
    var chaveAES = await crypto.subtle.generateKey(
        { name: 'AES-CBC', length: 256 },
        true,
        ['encrypt']
    );

    // gera o IV (vetor de inicializacao) aleatorio de 16 bytes
    // o IV e necessario pro AES-CBC, garante que mesmo dado cifrado duas vezes fica diferente
    var iv = crypto.getRandomValues(new Uint8Array(16));

    // cifra os dados do formulario com AES-256-CBC
    var dadosCodificados = new TextEncoder().encode(JSON.stringify(dados));
    var dadosCifrados = await crypto.subtle.encrypt(
        { name: 'AES-CBC', iv: iv },
        chaveAES,
        dadosCodificados
    );

    // exporta a chave AES como bytes brutos pra poder cifrar com RSA
    var chaveAESBruta = await crypto.subtle.exportKey('raw', chaveAES);

    // cifra a chave AES com RSA usando a chave publica do servidor
    // so o servidor consegue abrir porque so ele tem a chave privada
    var chaveCifrada = await crypto.subtle.encrypt(
        { name: 'RSA-OAEP' },
        chaveRSA,
        chaveAESBruta
    );

    // retorna tudo em base64 pra enviar via JSON
    return {
        dados_criptografados: bufferParaBase64(dadosCifrados),
        chave_criptografada:  bufferParaBase64(chaveCifrada),
        iv:                   bufferParaBase64(iv)
    };
}
