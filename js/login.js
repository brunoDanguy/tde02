// escuta o submit do formulario de login
document.getElementById('form-login').addEventListener('submit', async function(e) {
    e.preventDefault();

    var btn   = document.getElementById('btn-submit');
    var email = document.getElementById('email').value.trim();
    var senha = document.getElementById('senha').value;

    btn.disabled = true;
    btn.textContent = 'Criptografando...';

    try {
        // criptografa o email e a senha antes de mandar pro servidor
        // mesmo esquema do cadastro: AES pros dados, RSA pra chave do AES
        var payload = await criptografarHibrido({ email, senha });

        btn.textContent = 'Autenticando...';

        // envia pra o PHP so os dados ja cifrados
        var resposta = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        var resultado = await resposta.json();

        if (resultado.sucesso) {
            // salva os dados do usuario no sessionStorage do navegador
            // sessionStorage some quando fecha o navegador (diferente do localStorage)
            sessionStorage.setItem('usuario', JSON.stringify(resultado.usuario));
            mostrarAlerta('Login realizado! Redirecionando...', 'sucesso');
            setTimeout(function() { location.href = 'dashboard.html'; }, 1500);
        } else {
            mostrarAlerta(resultado.erro, 'erro');
        }

    } catch (err) {
        mostrarAlerta('Erro: ' + err.message, 'erro');
    }

    btn.disabled = false;
    btn.textContent = 'Entrar';
});

// exibe a mensagem de erro ou sucesso na tela
function mostrarAlerta(msg, tipo) {
    var el = document.getElementById('alerta');
    el.textContent = msg;
    el.className = 'alerta ' + tipo;
    el.style.display = 'block';
}
