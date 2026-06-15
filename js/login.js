// pega o formulario e escuta o evento de submit
document.getElementById('form-login').addEventListener('submit', async function(e) {
    e.preventDefault(); // impede o formulario de recarregar a pagina

    var btn   = document.getElementById('btn-submit');
    var email = document.getElementById('email').value.trim();
    var senha = document.getElementById('senha').value;

    btn.disabled = true;
    btn.textContent = 'Criptografando...';

    try {
        // chama a funcao do cripto.js que faz a criptografia hibrida
        var payload = await criptografarHibrido({ email, senha });

        btn.textContent = 'Autenticando...';

        // envia os dados ja criptografados pro PHP
        var resposta = await fetch('php/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        var resultado = await resposta.json();

        if (resultado.sucesso) {
            // salva os dados do usuario na sessao do navegador
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

function mostrarAlerta(msg, tipo) {
    var el = document.getElementById('alerta');
    el.textContent = msg;
    el.className = 'alerta ' + tipo;
    el.style.display = 'block';
}
