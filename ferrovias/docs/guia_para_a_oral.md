# Guia para a verificacao oral

Nomes de perfil: no codigo e no banco sao `administrador`, `maquinista` e `usuario_comum`; na tela aparecem como Gestor, Maquinista e Funcionario (design da equipe). A correspondencia esta no README.

Para cada pergunta: abra o arquivo, aponte o trecho e explique com as suas palavras. Cada integrante deve ler o codigo antes (hora de estudo conjunto do guia de fechamento). Este quadro so diz **onde olhar**.

## As seis perguntas do fechamento
| Pergunta | Onde olhar | Ideia central |
|---|---|---|
| Como o login funciona? | `index.php` | Formulario por POST; busca o usuario com consulta preparada; `password_verify` compara a senha digitada com o hash; se bate, cria a sessao e redireciona (`header` + `exit`); se nao, mesma mensagem de erro. |
| Por que a senha e guardada embaralhada? | `index.php`, `usuarios_form.php` | O hash nao tem volta: nem quem acessa o banco le a senha. So da para conferir. Por isso existe "redefinir senha" e nunca "ver minha senha". |
| O que e a `$_SESSION` e o que guardamos? | `index.php` (cria), `includes/proteger.php` (le), `includes/cabecalho.php` (mostra), `sair.php` (destroi) | Memoria do sistema sobre quem esta usando. Guarda id, login, nome e perfil. |
| Onde fica a conexao com o banco? | `config/conexao.php` | E o unico arquivo que conhece servidor, usuario, senha e banco; as demais paginas so incluem. Verifica `connect_error` (sem isso, tela em branco) e define `utf8mb4`. |
| Onde esta a consulta que traz os trens para a tela? | `trens.php` | Inclui a conexao, monta a consulta (condicoes acumuladas em array), prepara, executa, e um `while` desenha uma linha da tabela por registro. Demonstracao: apagar um trem no phpMyAdmin e recarregar. |
| Por que a consulta usa `?` no lugar do valor? | `trens.php`, `sensores.php`, `rotas.php` | O valor vai separado (`bind_param`), nunca vira parte do comando. O que o usuario digita e sempre texto, nunca instrucao (evita SQL injection). |
| Como o sistema sabe que um sensor pertence a um trem? | `banco/script.sql` (`id_trem` + `FOREIGN KEY`), `sensores.php` | A tabela guarda o numero; a consulta faz `INNER JOIN trens` e mostra o prefixo. |

## Bloco 1 e 2: acesso, sessao e cadastros
| Pergunta | Onde olhar | Ideia central |
|---|---|---|
| Linha que impede abrir pagina sem login | primeira linha de cada tela: `require includes/proteger.php` | Se nao ha `usuario_id` na sessao, redireciona ao login e `exit`. |
| Entrar como maquinista: o que muda? | `includes/cabecalho.php` (menu), `includes/permissao.php` | `temPapel()` decide o menu; `exigirPapel()` no topo da pagina protege de verdade. Esconder o menu nao protege. |
| Maquinista digita o endereco de usuarios | `usuarios.php`, `exigirPapel(['administrador'])` | Resposta 403, acesso negado. |
| O que acontece no servidor ao clicar em Sair? | `sair.php` | Retoma a sessao, limpa as variaveis, expira o cookie, destroi a sessao, redireciona e para. |
| Por que `proteger.php` consulta o banco a cada pagina? | `includes/proteger.php` | Para o usuario desativado ou com perfil alterado perder o acesso na hora. |
| Prefixo duplicado | `trens_form.php`, `script.sql` (`UNIQUE`) | Verificacao no PHP da mensagem clara; o `UNIQUE` garante mesmo se o PHP falhar. |
| Por que a exclusao e um formulario e nao um link? | `trens.php` (`POST` + `excluir_id`) | Link pode ser acionado por engano ou por pre-carregamento. O `confirm` e conforto; a seguranca e o PHP verificar perfil antes do `DELETE`. |
| Onde o valor da busca entra na consulta? | `trens.php` | So `?` na consulta; o `%termo%` vai pela variavel no `bind_param`. |
| O que acontece com sensores se o trem for excluido? | `script.sql` (`ON DELETE RESTRICT`), `trens.php` (erro 1451) | A exclusao e bloqueada com mensagem. Decisao: preservar historico. |
| Por que `trens.php` e nao `listagem.php`? | Guia de Uniformizacao de Telas | Nome canonico, com extensao `.php`. |
| Tela no celular e no computador | `includes/cabecalho.php`, tabelas com `data-label`, `docs/estrutura_html.md`, `assets/css/estilo.css` | O HTML e o mesmo; o CSS decide: tabela no desktop, cartoes no celular. [COMPLETAR com o CSS que a equipe fizer] |

## Bloco 3: leituras, alertas e rotas
| Pergunta | Onde olhar | Ideia central |
|---|---|---|
| Rodar o simulador e ver as leituras no banco | `simulador.php` | Laco que grava uma leitura por volta. |
| Por que preparar a consulta fora do laco? | `simulador.php` | O banco analisa o comando uma vez; no laco so mudam os valores. |
| Como o simulador sabe a faixa de valores? | `includes/limites.php` (`sortearValor`, `faixaNormal`) | Faixa por tipo do sensor, a partir do limite do tipo. |
| Onde esta o limite entre normal e critico? Por que ali? | tabela `limites`; `classificarValor()` em `includes/limites.php` | Um lugar so; ajustavel sem mexer no codigo. Normal ate o limite, atencao ate 10% acima, critico acima disso. |
| Gerar leitura fora do limite e ver o alerta | `simulador.php` (caixa "fora do limite") | A cada leitura nao normal, insere o alerta na mesma transacao. |
| A tabela de alertas guarda o prefixo do trem? | `notificacoes_alertas.php` | Nao: guarda `id_leitura`; o prefixo vem do JOIN alerta, leitura, sensor, trem. |
| Excluir a leitura ou o sensor: e os alertas? | `script.sql` (`ON DELETE CASCADE`) | Apagando a leitura, somem os alertas dela. Apagando o sensor, somem leituras e alertas. Trem com sensores nao exclui. |
| Dois filtros ao mesmo tempo em rotas | `rotas.php` | Array de condicoes unido com `AND`; os valores e seus tipos vao para o `bind_param`. |
| Rota para trem que ja tem viagem no mesmo horario | `rotas_form.php` (bloco "Avancado") | Bloqueia: faixas se cruzam quando partida existente menor que fim novo e fim existente maior que partida nova. Rota sem chegada ocupa o resto do dia. |
| Como a tela de sensores acha a ultima leitura? | `sensores.php` (subconsulta no `LEFT JOIN`) | Para cada sensor busca a leitura mais recente. |
| Sensor novo sem leitura aparece? | `sensores.php` | Sim: `LEFT JOIN` mantem o sensor e a tela mostra "Sem leitura". Com `INNER JOIN` ele sumiria. |
| Por que `leituras` tem indice e `trens` nao? | `script.sql` (`CREATE INDEX`) | Leituras tem milhares de linhas, filtradas por sensor e ordenadas por data. Trens tem dezenas. Indice acelera consultas, mas ocupa espaco e deixa a gravacao mais lenta. |
| Para que serve o `LIMIT`? | `leituras.php`, `notificacoes_alertas.php` | Sem ele a tela carregaria milhares de linhas e travaria. Ha paginacao. |

## Extras desta versao
| Pergunta | Onde olhar | Ideia central |
|---|---|---|
| Como funciona a recuperacao de senha? | `recupera_senha.php` | Codigo aleatorio de uso unico, valido por 30 minutos; no banco fica so o hash dele. |
| Por que o relatorio inclui o ultimo dia inteiro? | `visualiza_relatorio.php` | Compara `data_hora < dia seguinte`, em vez de ate meia-noite do ultimo dia. |
