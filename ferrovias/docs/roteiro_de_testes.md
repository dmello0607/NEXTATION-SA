# Roteiro de testes

Como usar: a coluna **Pre-teste** foi executada pela IA em um ambiente de teste (PHP 8.3 com servidor embutido e MariaDB 10.11), com requisicoes HTTP. A coluna **Passou?** e para a equipe preencher ao repetir o teste no XAMPP, em dupla (um opera, o outro anota).

| N | O que testar | Resultado esperado | Pre-teste (IA) | Passou? (equipe, no XAMPP) | Observacao |
|---|---|---|---|---|---|
| 1 | Importar o script em banco vazio | Cria tudo, sem erro | OK: banco apagado e importado do zero; reimportado varias vezes | ( ) sim ( ) nao | |
| 2 | Abrir o sistema pelo localhost | Tela de login aparece | OK (HTTP 200) | ( ) sim ( ) nao | Confirmar em http://localhost/ferrovias |
| 3 | Entrar com senha errada | Mensagem de erro, sem entrar | OK: "Login ou senha invalidos." | ( ) sim ( ) nao | Mesma mensagem para login inexistente |
| 4 | Entrar com usuario do script | Entra e vai para a tela principal | OK: admin, maquinista e usuario entram no painel | ( ) sim ( ) nao | Senha 123456 |
| 5 | Abrir tela interna em janela anonima | Redireciona para o login | OK: todas as telas internas redirecionam sem sessao | ( ) sim ( ) nao | |
| 6 | Conferir o topo da tela | Mostra o nome de quem esta logado | OK: "Conectado como <nome> (<cargo>)", por exemplo (Gestor) | ( ) sim ( ) nao | |
| 7 | Cadastrar um trem | Aparece na listagem | OK | ( ) sim ( ) nao | |
| 8 | Cadastrar um trem com campo vazio | Mensagem, sem gravar | OK: mensagens por campo, nada gravado | ( ) sim ( ) nao | |
| 9 | Editar e excluir um trem | A alteracao persiste ao recarregar | OK. Excluir trem com sensores e bloqueado com mensagem | ( ) sim ( ) nao | Decisao ON DELETE RESTRICT |
| 10 | Cadastrar um sensor | Escolhe o trem em lista, nao digitando | OK: lista vinda do banco; trem inexistente e recusado no servidor | ( ) sim ( ) nao | |
| 11 | Olhar a senha no phpMyAdmin | Esta embaralhada, nao legivel | OK: hash bcrypt comecando com `$2y$`, 60 caracteres | ( ) sim ( ) nao | |
| 12 | Sair e apertar o botao voltar | Nao volta para dentro do sistema | OK por requisicao: apos sair, pagina interna redireciona. Paginas internas enviam `no-store`. | ( ) sim ( ) nao | **Testar no navegador**; se mostrar a pagina, recarregar com Ctrl+F5 (cache) |

## Testes que falharam e foram corrigidos
- Cadastro de usuario (`usuarios_form.php`): falhou no ambiente de teste porque a funcao `mb_strlen` nao estava disponivel; corrigido trocando por `strlen`.
- Criacao inicial dos arquivos das subpastas falhou por erro de comando; os arquivos foram recriados e testados.
- [PREENCHER: outros defeitos que a equipe encontrar no XAMPP e como foram corrigidos]

## Testes extras executados no pre-teste
- Perfis: matriz completa de acesso (administrador, maquinista, usuario comum, sem login) em todas as telas.
- Trens: busca, filtro de status, filtros combinados, prefixo duplicado, edicao, exclusao por POST, contagem de sensores.
- Sensores: filtros por tipo e trem, sensor sem leitura continua na lista, trem inexistente recusado.
- Rotas: quatro filtros isolados e combinados, periodo com so uma data, sobreposicao de horarios (bloqueia), rota cancelada nao conflita, filtros mantidos ao voltar.
- Simulador: quantidade, faixas por tipo, horarios distintos, valores fora do limite, rollback quando a gravacao do alerta falha.
- Leituras e alertas: paginacao, filtros combinados, destaque do critico, cascata ao excluir leitura/sensor.
- Usuarios: desativar usuario logado derruba a sessao, nao desativa a si mesmo, editar com e sem troca de senha.
- Relatorios: ultimo dia do periodo incluido, filtros, relatorio vazio.
- Recuperar senha: token de uso unico, expirado, invalido, hash no banco, usuario desativado.
