# Sistema de Monitoramento Ferroviario (Nextation)

## Equipe
[PREENCHER: nomes dos integrantes e turma]

## O que o sistema faz
O sistema monitora uma frota de trens. Cada trem tem sensores (velocidade, temperatura do motor, consumo de energia) que enviam leituras. Como nao ha sensores fisicos, um simulador gera as leituras no banco.

Toda leitura acima do limite do seu tipo vira um alerta automatico, classificado em atencao ou critico, que aparece na central de alertas e no painel. O sistema tambem cadastra trens, sensores, rotas e viagens programadas, gera relatorios de alertas por trem e periodo, e controla quem acessa o que por perfil (administrador, maquinista e usuario comum).

O acesso exige login. As senhas ficam guardadas como hash e cada tela verifica a sessao e o perfil no servidor.

## Tecnologias
PHP 8 (mysqli, consultas preparadas), MySQL/MariaDB, HTML, CSS, JavaScript (apenas confirmacao de exclusao e botao de imprimir). Ambiente: XAMPP.

## Como instalar
1. Copiar a pasta `ferrovias` para `C:\xampp\htdocs\`
2. Iniciar Apache e MySQL no painel do XAMPP
3. No phpMyAdmin, importar `banco/script.sql` (cria o banco `ferrovias_db`, as tabelas e os dados de exemplo; pode ser importado quantas vezes for preciso)
4. Acessar http://localhost/ferrovias

A conexao usa usuario `root` sem senha (padrao do XAMPP). Se for diferente, ajustar so o arquivo `config/conexao.php`.

## Usuarios de teste
| Login | Senha | Perfil |
|---|---|---|
| admin | 123456 | administrador (Gestor) |
| maquinista | 123456 | maquinista (Maquinista) |
| usuario | 123456 | usuario_comum (Funcionario) |

## Telas do sistema
| Arquivo | O que faz |
|---|---|
| `index.php` | Login |
| `recupera_senha.php` | Recuperacao de senha (codigo simulado na tela, valido por 30 minutos) |
| `sair.php` | Encerra a sessao |
| `dashboard.php` | Painel: frota, alertas, sensores criticos por trem, proximas viagens (conteudo varia por perfil) |
| `trens.php` / `trens_form.php` | Listar, buscar, filtrar por status, cadastrar, editar e excluir trens |
| `sensores.php` / `sensores_form.php` | Gestao de sensores, com filtro por tipo e trem e indicador da ultima leitura |
| `rotas.php` / `rotas_form.php` | Rotas e viagens programadas, com quatro filtros e bloqueio de horarios sobrepostos |
| `simulador.php` | Gera leituras (e alertas) como se viessem dos sensores |
| `leituras.php` | Leituras recebidas, com filtro por sensor e trem |
| `notificacoes_alertas.php` | Central de alertas, filtros por severidade e tipo, critico em destaque |
| `usuarios.php` / `usuarios_form.php` | Listar, cadastrar, editar e ativar/desativar usuarios |
| `gera_relatorio.php` / `visualiza_relatorio.php` | Relatorio de alertas por trem e periodo, com historico |

Arquivos de apoio: `config/conexao.php` (unico lugar com os dados do banco), `includes/proteger.php` (exige sessao), `includes/permissao.php` (verifica perfil), `includes/cabecalho.php` e `rodape.php` (topo e menu), `includes/funcoes.php` (utilidades), `includes/limites.php` (limites e classificacao de severidade).

## Decisoes tomadas
Os valores de perfil gravados no banco e usados no codigo seguem o Guia de Uniformizacao de Telas: `administrador`, `maquinista` e `usuario_comum`. Na tela, seguindo o design da equipe, aparecem como **Gestor** (administrador), **Maquinista** e **Funcionario** (usuario comum). Telas ficam na raiz com nome canonico, tabelas no plural.

| Decisao | Escolha | Justificativa e consequencia futura |
|---|---|---|
| Tipo da coluna `papel` | ENUM | O proprio banco recusa perfil invalido. Consequencia: criar um perfil novo exige ALTER TABLE. |
| Forma de conexao | mysqli orientado a objetos | Mesma forma do projeto base e das aulas; usada em todo o sistema, sem misturar. |
| Algoritmo da senha | `PASSWORD_DEFAULT` (bcrypt) | Acompanha as versoes do PHP; o hash cabe em `VARCHAR(255)`. |
| Perfis autorizados | Administrador: tudo. Maquinista: consulta sensores, leituras, alertas e rotas. Usuario comum: so painel (frota e proximas viagens). | Segue o guia de telas. Consequencia: o maquinista ve todos os trens, porque nao ha vinculo maquinista-trem no banco. |
| Unicidade do prefixo do trem | `UNIQUE` no banco e verificacao no PHP antes de gravar | O banco garante mesmo se o PHP falhar; o PHP da mensagem clara. |
| Status do trem | ENUM (`em_operacao`, `manutencao`, `inativo`), exibidos como Ativo, Manutencao e Parado | Impede valor invalido. Cores: verde, amarelo e vermelho. |
| Estrutura do HTML | Casco unico (`includes/cabecalho.php` e `rodape.php`), tabelas com `data-label` que viram cartoes por CSS | O mesmo HTML serve ao desktop e ao celular; so o CSS muda. Ver `docs/estrutura_html.md`. |
| `ON DELETE` de trem com sensores ou rotas | `RESTRICT` (impede a exclusao) | Em monitoramento, apagar o historico junto com o trem e indesejavel. Consequencia: para tirar um trem, remover antes os sensores e rotas (ou deixar o trem inativo). |
| Vinculo sensor-trem | Obrigatorio (`id_trem NOT NULL`); segmento e informacao adicional | Resolve a divergencia entre os guias; o painel precisa de leitura por trem. |
| Data e hora da rota | Campos separados (`DATE` e `TIME`) | Facilita o filtro por data. Consequencia: viagens que atravessam a meia-noite nao sao tratadas. |
| Formato da leitura | Valor generico + unidade | Aceita sensor novo sem alterar a tabela. Consequencia: comparar medidas diferentes exige consultas mais elaboradas. |
| Local dos limites | Tabela `limites` no banco | Ajuste sem mexer no codigo. Consequencia: ha uma consulta a mais ao classificar. |
| Criterio de severidade | Ate o limite: normal. Ate 10% acima: atencao. Mais de 10% acima: critico. | Regra unica na funcao `classificarValor` (`includes/limites.php`). |
| Alertas e notificacoes | Uma tabela so (`alertas`), exibida em `notificacoes_alertas.php` | Simplicidade. Consequencia: ainda nao ha aviso entregue a alguem (e-mail, push); exigiria tabela de notificacoes. |
| Leitura e alerta | Gravados na mesma transacao | Nunca existe leitura fora do limite sem alerta, nem alerta sem leitura. |
| Exclusao de leitura/sensor | `ON DELETE CASCADE` | Os alertas acompanham a leitura; as leituras acompanham o sensor. |
| Exclusao de usuario | Nao existe: o administrador desativa | Preserva o historico (relatorios gerados). A sessao e conferida no banco a cada pagina, entao desativar vale na hora. |
| Recuperacao de senha | Codigo de uso unico, valido por 30 minutos, guardado como hash | O envio por e-mail e simulado na tela. Em producao, trocar por e-mail real. |
| Indices | Em `leituras` (`id_sensor` e `data_hora`) | Tabela que cresce e e filtrada por sensor e ordenada por data. Nas demais (dezenas de linhas) um indice nao compensa. |
| Volume de dados | `LIMIT` e paginacao em leituras e alertas; limite de 500 linhas no detalhe do relatorio | Evita travar o navegador. |

## Limitacoes conhecidas
- Interface visual: o HTML segue o design (Figma), mas o CSS responsivo (tabela no desktop e cartoes no celular) e o acabamento visual [PREENCHER: remover esta linha se o CSS for concluido].
- Itens do design sem funcionalidade correspondente: cadastro publico de conta, login com Google, e-mail do usuario, status operacional e dados do maquinista, visualizacao de usuario com horas e tarefas, e campos do trem como categoria, carga, linha, terminal e maquinista (lista completa em `docs/estrutura_html.md`).
- O maquinista ve todos os trens, sensores e alertas, nao apenas os do trem que opera (nao ha vinculo maquinista-trem no banco).
- O usuario comum nao tem compra de passagens nem acompanhamento de viagens proprias; ve apenas o painel.
- Recuperacao de senha sem e-mail real: o codigo aparece na tela.
- Sem protecao contra tentativas repetidas de login e sem token CSRF nos formularios.
- Viagens que atravessam a meia-noite nao sao suportadas (a chegada deve ser depois da partida, no mesmo dia).
- Sem notificacoes entregues a pessoas (so a central de alertas).
- Relatorio em PDF depende da impressao do navegador ("salvar como PDF").
- Listas de trens, sensores, rotas e usuarios sem paginacao.
- Sem testes automatizados; os testes estao no roteiro manual (`docs/roteiro_de_testes.md`).

## Uso de inteligencia artificial
Declaracao: a maior parte do codigo PHP e SQL deste repositorio (backend) e dos documentos em `docs/` foi gerada pela IA **Claude (Anthropic)**, a partir dos guias dos blocos 1 a 3 e do guia de fechamento fornecidos pelo professor. [PREENCHER: quem escreveu o HTML/CSS, se houve uso de IA nele, e ajustar a tabela ao modelo exigido pela unidade curricular.]

| Parte | Ferramenta | O que foi pedido | O que a IA gerou | Como foi verificado | Quem da equipe domina |
|---|---|---|---|---|---|
| 1. Banco, conexao, login, sessao, sair, cadastro de usuarios | Claude | Backend do bloco 1 e base do bloco 2 | `script.sql`, `conexao.php`, `index.php`, `proteger.php`, `sair.php`, `usuarios_form.php` | Requisicoes de teste (login certo/errado, sem sessao, perfil) | [PREENCHER] |
| 2. Trens e sensores | Claude | CRUD, busca, filtros, relacionamento | `trens*.php`, `sensores*.php`, `limites.php`, `funcoes.php` | CRUD, filtros, validacoes, exclusao bloqueada, permissoes | [PREENCHER] |
| 3. Rotas | Claude | Rotas com 4 filtros e bloqueio de sobreposicao | `rotas*.php` | Filtros isolados e combinados, sobreposicao, permissoes | [PREENCHER] |
| 4. Simulador, leituras e alertas | Claude | Simulador, leituras, central de alertas | `simulador.php`, `leituras.php`, `notificacoes_alertas.php` | Valores por tipo, transacao com falha forcada, consistencia leitura/alerta, cascata | [PREENCHER] |
| 5. Painel e usuarios | Claude | Painel por perfil, gestao de usuarios | `dashboard.php`, `usuarios*.php`, revalidacao em `proteger.php` | Perfis, desativar usuario logado, protecao da propria conta | [PREENCHER] |
| 6. Relatorios e recuperar senha | Claude | Relatorio de alertas, recuperacao de senha | `gera_relatorio.php`, `visualiza_relatorio.php`, `recupera_senha.php` | Periodo (ultimo dia incluido), token expirado/usado/hash | [PREENCHER] |
| 7. Documentacao | Claude | README, roteiro de testes, guia para a verificacao oral, texto do manual | `README.md`, `docs/*` | Revisao pela equipe | [PREENCHER] |
| 8. Estrutura HTML | Claude | Montar o HTML das telas a partir do design (Figma exportado em PDF), sem CSS | HTML dentro dos arquivos PHP, `includes/cabecalho.php`, `docs/estrutura_html.md`, `assets/img/logo.png` (recortado do design) | Validacao automatica da estrutura de todas as telas e regressao funcional (80 verificacoes) | [PREENCHER] |
