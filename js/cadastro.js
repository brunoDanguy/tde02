// pega o formulario e escuta o evento de submit
document.getElementById('form-cadastro').addEventListener('submit', async function(e) {
    e.preventDefault(); // impede o formulario de recarregar a pagina

    var btn   = document.getElementById('btn-submit');
    var nome  = document.getElementById('nome').value.trim();
    var email = document.getElementById('email').value.trim();
    var senha = document.getElementById('senha').value;
    var conf  = document.getElementById('confirmar-senha').value;

    if (senha !== conf) {
        mostrarAlerta('As senhas nao coincidem.', 'erro');
        return;
    }

    if (senha.length < 6) {
        mostrarAlerta('Senha deve ter ao menos 6 caracteres.', 'erro');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Criptografando...';

    try {
        // chama a funcao do cripto.js que faz a criptografia hibrida
        var payload = await criptografarHibrido({ nome, email, senha });

        btn.textContent = 'Enviando...';

        // envia os dados ja criptografados pro PHP
        var resposta = await fetch('php/cadastro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        var resultado = await resposta.json();

        if (resultado.sucesso) {
            mostrarAlerta(resultado.mensagem + ' Redirecionando...', 'sucesso');
            setTimeout(function() { location.href = 'login.html'; }, 2000);
        } else {
            mostrarAlerta(resultado.erro, 'erro');
        }

    } catch (err) {
        mostrarAlerta('Erro: ' + err.message, 'erro');
    }

    btn.disabled = false;
    btn.textContent = 'Cadastrar';
});

function mostrarAlerta(msg, tipo) {
    var el = document.getElementById('alerta');
    el.textContent = msg;
    el.className = 'alerta ' + tipo;
    el.style.display = 'block';
}
