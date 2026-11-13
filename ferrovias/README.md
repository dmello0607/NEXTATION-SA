# Nextation — Sistema de Monitoramento Ferroviário

Projeto de monitoramento de uma frota de trens, desenvolvido em PHP e MySQL/MariaDB para execução local no XAMPP. A interface utiliza tema escuro e layout responsivo, com base nos mockups da equipe.

## Equipe

Preencher os nomes dos integrantes e a turma.

## Funcionalidades

- Login, recuperação de senha e controle de acesso por perfil.
- Painel com situação da frota, monitoramento, alertas e próximas viagens, conforme o perfil.
- Cadastro, edição, busca, filtros e exclusão de trens, sensores e rotas.
- Validação de conflitos de horários nas viagens do mesmo trem.
- Simulação de leituras de velocidade, temperatura do motor e consumo de energia.
- Classificação de leituras e geração de alertas acima dos limites cadastrados.
- Consulta de leituras e alertas com filtros e paginação.
- Cadastro, edição e ativação/desativação de usuários.
- Relatórios de alertas por trem e período, com histórico e impressão para salvar em PDF.

## Tecnologias

| Tecnologia | Uso |
|---|---|
| PHP 8 com extensão `mysqli` | Aplicação, sessões e consultas preparadas |
| MySQL/MariaDB | Banco de dados |
| HTML e CSS | Interface responsiva e tema escuro |
| JavaScript | Confirmação de ações e impressão pelo navegador |
| XAMPP | Ambiente local com Apache, PHP e MariaDB |
| Flaticon Uicons Regular Rounded 3.0.0 | Ícones locais da interface |

Não há etapa de compilação nem instalação de dependências com npm ou Composer.

## Como executar no XAMPP

1. Mantenha o repositório em `C:\xampp\htdocs\Nextation\NEXTATION-SA`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. Abra o [phpMyAdmin](http://localhost/phpmyadmin), vá em **Importar** e selecione [banco/script.sql](banco/script.sql), dentro da pasta `ferrovias`.
4. Confirme que o banco `ferrovias_db` foi criado.
5. Abra [Nextation no navegador](http://localhost/Nextation/NEXTATION-SA/ferrovias/).

**Atenção:** importar novamente o script apaga e recria as tabelas do sistema, substituindo os registros pelos dados de exemplo. Faça backup antes de reimportar um banco com dados que deseja manter.

Se preferir copiar apenas a pasta `ferrovias` para `C:\xampp\htdocs`, o endereço passa a ser `http://localhost/ferrovias/`.

### Conexão com o banco

As configurações ficam em [config/conexao.php](config/conexao.php):

| Configuração | Valor padrão |
|---|---|
| Servidor | `localhost` |
| Usuário | `root` |
| Senha | Vazia |
| Banco | `ferrovias_db` |
| Codificação | `utf8mb4` |

Ajuste esse arquivo se o seu ambiente utilizar outro usuário, senha ou servidor.

### Contas de teste

| Login | Senha | Perfil exibido |
|---|---|---|
| `admin` | `123456` | Gestor |
| `maquinista` | `123456` | Maquinista |
| `usuario` | `123456` | Funcionário |

A página inicial sempre mostra o formulário de login, mesmo quando já existe uma sessão ativa. Após autenticar, o usuário é direcionado ao painel. Abrir a página de login não encerra uma sessão existente; para encerrá-la, use **Mais → Sair**.

### Problemas comuns

| Problema | O que verificar |
|---|---|
| Não foi possível conectar ao banco | MySQL iniciado, banco `ferrovias_db` importado e credenciais em `config/conexao.php` |
| Página não encontrada | Apache iniciado, localização da pasta e endereço correspondente no navegador |
| CSS ou ícones antigos | Atualize com **Ctrl + F5** |
| Login recusado | Use uma conta de teste e confirme que ela está ativa |
| Próximas viagens vazias | Os exemplos usam datas fixas; cadastre uma viagem futura com o perfil Gestor |

## Perfis de acesso

| Perfil na interface | Valor no banco | Permissões |
|---|---|---|
| Gestor | `administrador` | Acesso a todos os módulos e ações de gestão |
| Maquinista | `maquinista` | Painel e consulta de sensores, leituras, alertas e rotas |
| Funcionário | `usuario_comum` | Painel com situação da frota e próximas viagens |

As permissões são verificadas no PHP. A cada página interna, a aplicação também consulta se o usuário continua ativo e atualiza seu nome e perfil na sessão.

## Telas e arquivos

| Arquivo | Função |
|---|---|
| `index.php` | Tela de login |
| `recupera_senha.php` | Solicitação de código e redefinição de senha |
| `sair.php` | Encerramento da sessão |
| `dashboard.php` | Painel com conteúdo conforme o perfil |
| `trens.php` / `trens_form.php` | Listagem e formulário de trens |
| `sensores.php` / `sensores_form.php` | Listagem e formulário de sensores |
| `rotas.php` / `rotas_form.php` | Rotas e viagens programadas |
| `simulador.php` | Geração de leituras e alertas |
| `leituras.php` | Consulta de leituras |
| `notificacoes_alertas.php` | Central de alertas |
| `usuarios.php` / `usuarios_form.php` | Gestão de usuários |
| `gera_relatorio.php` / `visualiza_relatorio.php` | Geração, histórico e visualização de relatórios |

```text
ferrovias/
├── assets/
│   ├── css/estilo.css        # Estilos compartilhados e responsividade
│   ├── fonts/               # Fonte local de ícones e referência de licença
│   └── img/logo.png         # Logo do projeto
├── banco/script.sql         # Estrutura e dados de exemplo
├── config/conexao.php       # Conexão com MySQL/MariaDB
├── docs/                    # Guias, manual e roteiro de testes
├── includes/
│   ├── cabecalho.php        # Cabeçalho e navegação
│   ├── rodape.php           # Fechamento da página e crédito dos ícones
│   ├── proteger.php         # Sessão e situação do usuário
│   ├── permissao.php        # Controle de acesso por perfil
│   ├── funcoes.php          # Utilidades, mensagens e rótulos
│   └── limites.php          # Limites e classificação das leituras
└── *.php                    # Telas do sistema
```

## Interface e ícones

O CSS compartilhado define fundo escuro, superfícies arredondadas, ações em azul e estados em verde, amarelo e vermelho. No desktop, a navegação fica na lateral; no celular, na barra inferior. As listas são adaptadas para cartões em telas pequenas; as de trens e usuários também usam cartões em desktops maiores.

A interface foi adaptada aos campos e funcionalidades existentes no PHP e no banco. Os mockups incluem recursos adicionais que ainda não estão implementados, listados nas limitações abaixo.

Os ícones são **Uicons by [Flaticon](https://www.flaticon.com/uicons)**, na família Regular Rounded 3.0.0. A fonte está em `assets/fonts/uicons-regular-rounded.woff2`, com as referências de origem e licença em [assets/fonts/FLATICON.txt](assets/fonts/FLATICON.txt). Depois de baixados, os ícones não dependem de internet. O crédito também aparece no rodapé das telas internas.

## Regras e decisões do sistema

- **Senhas:** armazenadas com `password_hash(..., PASSWORD_DEFAULT)` e verificadas com `password_verify`.
- **Consultas:** utilizam `mysqli` e parâmetros preparados nos filtros e operações com dados recebidos.
- **Trem:** prefixo único; status `em_operacao`, `manutencao` ou `inativo`, exibidos como Ativo, Manutenção e Parado.
- **Sensor:** sempre vinculado a um trem. Localização e segmento são informações adicionais.
- **Severidade:** até o limite é normal; até 10% acima é atenção; acima disso é crítico. Os limites ficam na tabela `limites`.
- **Simulação:** leitura e alerta são gravados na mesma transação.
- **Exclusões:** trens com sensores ou rotas vinculadas não podem ser excluídos. Excluir um sensor remove suas leituras e alertas em cascata.
- **Usuários:** são desativados, preservando o histórico. O gestor não pode desativar a própria conta nem rebaixar o próprio perfil.
- **Rotas:** viagens do mesmo trem não podem ter horários sobrepostos no mesmo dia. Uma viagem sem chegada definida ocupa o restante do dia.
- **Recuperação de senha:** código de uso único, válido por 30 minutos, armazenado como hash; o envio por e-mail é simulado na tela.
- **Relatórios:** registram autor, filtros e data de geração; o detalhe exibe até 500 alertas. A exportação em PDF utiliza a impressão do navegador.

## Limitações conhecidas

- Não há sensores físicos: as leituras são geradas pelo simulador.
- Não há cadastro público, autenticação com Google ou e-mail do usuário. O acesso utiliza login e senha.
- O maquinista consulta dados de todos os trens, pois não existe vínculo entre maquinista e trem no banco.
- Campos dos mockups como categoria do trem, carga, linha, terminal, maquinista e horas trabalhadas ainda não estão disponíveis.
- O usuário comum não compra passagens nem acompanha viagens próprias.
- A recuperação de senha não envia e-mail real.
- Não há notificações por e-mail ou push; os alertas são consultados no sistema.
- Viagens que atravessam a meia-noite não são suportadas.
- Não há limitação de tentativas de login nem proteção CSRF nos formulários.
- As listas de trens, sensores, rotas e usuários não têm paginação.
- Não existe uma suíte de testes automatizados versionada no repositório.

## Documentação e verificação

- [Roteiro de testes](docs/roteiro_de_testes.md): cenários manuais e registros de pré-testes anteriores.
- [Estrutura HTML](docs/estrutura_html.md): classes, componentes e diferenças entre os mockups e o sistema.
- [Manual do usuário](docs/manual_do_usuario_texto.md): texto-base para o manual, com espaços para capturas.
- [Guia para a verificação oral](docs/guia_para_a_oral.md): referências para explicar o código.

Durante a implementação visual, foram conferidas prévias de login, painel, lista e formulário de trens nas larguras de 320, 390, 768 e 1440 px. Após a criação do banco local, também foram verificados o login de gestor, o carregamento dos ícones e as páginas de painel, trens e usuários em desktop e celular. Essas conferências não substituem a execução completa do roteiro de testes pela equipe.

## Uso de inteligência artificial

A documentação original declara que o backend PHP/SQL e os documentos iniciais foram gerados com auxílio de **Claude (Anthropic)**, a partir dos guias fornecidos pelo professor. A estrutura HTML também foi atribuída ao Claude no README original.

As alterações desta etapa utilizaram **Codex (OpenAI)**:

| Parte | Trabalho realizado | Verificação realizada |
|---|---|---|
| Interface | CSS responsivo baseado nas telas fornecidas e links de navegação no desktop | Prévias em diferentes larguras e inspeção visual |
| Ícones | Integração local dos Uicons do Flaticon e crédito no rodapé | Carregamento da fonte no navegador e conferência de desktop/celular |
| Entrada do site | Remoção do redirecionamento automático para o painel ao abrir o login | Verificação de sintaxe PHP e revisão da alteração |
| Ambiente local | Importação do banco ausente a partir do script existente | Conferência das tabelas e conexão PHP |
| README | Atualização das instruções e do estado atual do projeto | Revisão dos caminhos, funcionalidades e alterações realizadas |

A equipe deve completar os nomes, a turma e a indicação de quem domina cada parte, além de conferir o formato de declaração exigido pela unidade curricular.
