# Nextation

---

## Seção 1 — Identificação e Visão Geral

### Identificação do Grupo

**Integrantes:** [Felipe D] [Henrique J] [Henrique M] [Henrique N]

**Link do Mockup:**  
[https://www.figma.com/design/mm6OIk3g11jmXcnP3axA3D/Nextation---SA-Mock-Up?node-id=0-1&t=BK9YuxXJluEh40WM-1]

### Resumo do Sistema

O sistema (Ferrorama) da Nextation é uma plataforma de gestão e monitoramento em tempo real de frotas ferroviárias e sensores de via, desenvolvida especificamente para operadores, técnicos e administradores de malhas ferroviárias. A aplicação centraliza telemetria de velocidade, temperatura e alertas críticos dos trens, permitindo manutenções preditivas e tomadas de decisão rápidas. O grande diferencial do sistema é a sua interface analítica simplificada que traduz dados complexos de sensores IoT em alertas visuais acionáveis, mitigando riscos de acidentes e atrasos logísticos.

### Stack Tecnológica Adotada e Justificativa

**Frontend Core:** HTML5 semântico, CSS3 e JavaScript Puro (Vanilla JS).

**Justificativa:** A opção pelo JavaScript puro visa consolidar os conceitos fundamentais de manipulação do DOM, escopo e assincronismo solicitados na Situação de Aprendizagem, sem a complexidade de setup ou curva de aprendizado inicial de frameworks como React ou Vue.

**Estilização e Layout:** Bootstrap 5 integrado via CDN + CSS Customizado.

**Justificativa:** O Bootstrap 5 será utilizado para acelerar a criação do layout responsivo (Grid/Flexbox) e componentes utilitários de formulários. O arquivo CSS customizado cobrirá a identidade visual específica do mockup (paleta de cores do Ferrorama, tipografia e espaçamentos personalizados).

**Armazenamento de Dados Intermediário:** localStorage e arquivos JSON simulados.

**Justificativa:** Para simular a persistência de dados de usuários, trens e logs de sensores sem a necessidade imediata de um banco de dados backend robusto nesta etapa.

---

## Seção 2 — Arquitetura de Arquivos e Pastas

### Árvore de Diretórios do Projeto

```text
ferorama-app/
├── README.md
├── docs/
│   └── PLANO_IMPLEMENTACAO.md
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   ├── style.css
│   │   └── components.css
│   ├── js/
│   │   ├── auth.js
│   │   ├── dados.js
│   │   ├── main.js
│   │   └── utils.js
│   ├── img/
│   │   ├── logo.svg
│   │   ├── icons/
│   │   └── avatars/
├── src/
│   ├── public/
│   │   ├── login.html
│   │   └── cadastro.html
│   ├── dashboard/
│   │   ├── index.html
│   │   ├── trens.html
│   │   ├── sensores.html
│   │   └── usuarios.html
│
└── index.html
```

### Justificativa da Estrutura

**Divisão por Contexto de Uso (Auth vs. Dashboard):** Separar as telas públicas (`src/public/`) das telas administrativas e operacionais (`src/dashboard/`) garante que a lógica de proteção de rotas via JavaScript possa ser aplicada em blocos. O arquivo principal `index.html` na raiz agirá como o redirecionador inicial do sistema.

**Separação dos Arquivos CSS (`style.css` vs. `components.css`):** O arquivo `style.css` cuidará do layout macro, variáveis globais (cores, fontes) e resets de páginas específicas. Já o `components.css` guardará estritamente as classes dos elementos reutilizáveis (botões, cards, tabelas), facilitando alterações de design globais sem quebrar estruturas de páginas específicas.

**Modularização dos Scripts JS (`auth.js`, `dados.js`, `main.js`):**

- `auth.js`: Trata exclusivamente de controle de sessão, login, cadastro e validação de permissões de usuário (Admin, Técnico, Operador).

- `dados.js`: Funciona como nosso "Mock Database", centralizando as funções de leitura e escrita do localStorage para trens, sensores e usuários.

- `main.js`: Cuida da manipulação do DOM específica das telas e inicialização de gráficos ou tabelas.

- `utils.js`: Funções utilitárias reutilizáveis, como formatação de datas e máscaras de inputs.


---

## Seção 3 — Componentes Reutilizáveis Identificados

A análise do mockup permitiu isolar componentes visuais que se repetem pela interface. Eles serão desenvolvidos de forma padronizada para evitar duplicidade de código.

| Componente | Telas em que Aparece | Variações Observadas |
|-------------|----------------------|----------------------|
| **Botão Primário** | Login, Cadastro, Modais de Adicionar Usuário/Trem | Estado normal, hover, foco e desabilitado (loading). |
| **Card de Informação Rápida** | Dashboard, Visão Geral de Trens, Sensores | Com ícone/métrica de sucesso (verde), com badge de alerta crítico (vermelho). |
| **Header / Navbar de Navegação** | Dashboard, Trens, Sensores, Usuários | Altera o link ativo dependendo da página; exibe foto e nível de acesso do perfil logado. |
| **Tabela de Dados** | Listagem de Trens, Histórico de Sensores, Gerenciamento de Usuários | Linhas alternadas (zebra), colunas com ordenação, badges de status integrados nas células. |
| **Modal de Confirmação** | Exclusão/Desativação de Usuários, Confirmação de Envio de Relatório | Variante de Alerta (Aviso em amarelo/vermelho) e Variante Informativa (Aviso em azul). |
| **Sidebar de Menu Lateral** | Todas as telas internas do Dashboard | Retraído (ícones apenas para mobile) e Expandido (com textos para desktop). |

---

## Seção 4 — Ordem de Implementação

A ordem de desenvolvimento foi planejada de modo a mitigar o risco de "bloqueio de dependências". Componentes básicos e lógicas de dados serão construídos antes das interfaces que os consomem.

### Fase 1: Setup de Estilos Globais e Mock de Dados (`assets/css/*` e `dados.js`)

**Justificativa:** Antes de desenhar as telas, precisamos das variáveis de cores configuradas e das estruturas de dados (arrays de objetos de trens e usuários) prontas para alimentar a interface.

### Fase 2: Componentes Reutilizáveis Base (Botões, Cards e Sidebar)

**Justificativa:** Serão codificados em um arquivo HTML isolado de testes para garantir que sua estilização e responsividade funcionem perfeitamente antes de serem distribuídos pelas páginas.

### Fase 3: Autenticação (`login.html`, `cadastro.html` e `auth.js`)

**Justificativa:** O fluxo básico de segurança do sistema precisa existir para que possamos capturar qual o nível de acesso do usuário (Admin, Técnico, Operador) antes de renderizar as telas internas.

### Fase 4: Dashboard Principal (`src/dashboard/index.html`)

**Justificativa:** É a tela central do sistema que consome os dados macros gerados pelo `dados.js`. Ela valida o fluxo de login construído na fase anterior.

### Fase 5: Telas de Gerenciamento e Operação (`trens.html`, `sensores.html`)

**Justificativa:** Telas de nível operacional. Dependem do layout base do dashboard e das tabelas estruturadas na Fase 1.

### Fase 6: Tela Administrativa (`usuarios.html`)

**Justificativa:** Última tela, restrita ao perfil Admin. Requer que todo o fluxo de controle de usuários no `auth.js` esteja robusto e testado.

---

## Seção 5 — Fluxos de Navegação do Usuário

### Fluxo 1: Cadastrar-se e acessar o sistema pela primeira vez

**Perfil do Usuário:** Novo usuário (Operador ou Técnico aguardando aprovação).

**Sequência de Telas e Ações:**

`login.html` → Usuário clica no link "Criar uma nova conta".

`cadastro.html` → Preenche Nome, E-mail, Senha e escolher seu Cargo Clica em "Cadastrar".

Sistema exibe um alerta/modal de sucesso de cadastro e limpa o formulário, redirecionando automaticamente após 2 segundos.

`login.html` → Inserir as credenciais recém-criadas $\rightarrow$ Clica em "Entrar".

`src/dashboard/index.html` → Sistema valida as credenciais e o usuário visualiza os cartões de resumo da frota.

---

### Fluxo 2: Operador identifica alerta de sensor crítico e visualiza detalhes do trem afetado

**Perfil do Usuário:** Operador da Malha.

**Sequência de Telas e Ações:**

`src/dashboard/index.html` → Na central de notificações ou nos cards de monitoramento rápidos, o operador vê um card piscando em vermelho com o texto "Alerta Crítico: Sensor Temp. Eixo - Trem TR-04".

**Ação:** O operador clica no botão "Ver Detalhes" localizado no próprio card de alerta.

`src/dashboard/sensores.html` → O sistema redireciona o operador para a tela de sensores já aplicando automaticamente um filtro pelo ID do Trem TR-04.

**Ação:** O operador analisa o gráfico/tabela com o histórico das últimas leituras do sensor de temperatura para tomar a decisão de parada técnica.

---

### Fluxo 3: Admin cadastra e ativa um novo usuário do sistema

**Perfil do Usuário:** Administrador do Sistema.

**Sequência de Telas e Ações:**

`src/dashboard/index.html` → No menu lateral (Sidebar), o Admin clica na opção "Gerenciar Usuários".

`src/dashboard/usuarios.html` → O Admin visualiza a tabela de usuários ativos e clica no botão primário "+ Adicionar Novo Usuário".

Modal se abre em tela exibindo o formulário de cadastro administrativo (Nome, E-mail, Nível de Permissão).

**Ação:** Admin preenche as informações e clica em "Salvar".

`src/dashboard/usuarios.html` → O modal fecha, a tabela atualiza dinamicamente injetando a nova linha e uma mensagem toast de confirmação aparece no topo da tela.

---

### Fluxo 4: Operador filtra a lista de trens por status

**Perfil do Usuário:** Operador ou Técnico.

**Sequência de Telas e Ações:**

`src/dashboard/index.html` → No menu lateral, clica em "Frotas de Trens".

`src/dashboard/trens.html` → Carrega a tela com a listagem completa de locomotivas e vagões monitorados.

**Ação:** O usuário clica no componente de dropdown ou grupo de botões de filtro e escolhe a opção "Em Manutenção".

**Sistema/JavaScript:** Dispara um evento que escuta a mudança, filtra o array de dados e re-renderiza a tabela exibindo exclusivamente os trens com o status selecionado.

---

## Seção 6 — Critérios de "Pronto" (Definition of Done)

Para garantir o padrão de qualidade combinado e a consistência visual do projeto S.A. Ferrorama, nenhuma tela ou funcionalidade será considerada finalizada se não cumprir integralmente o seguinte checklist:

- **HTML Semântico:** Uso obrigatório das tags estruturais corretas (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`) eliminando o uso excessivo de divs sem contexto (`<div>`).

- **Estilização Limpa:** Ausência total de CSS inline (`style="..."`) e ausência de seletores id desnecessários para estilo. Toda a estilização deve estar concentrada nas folhas de estilo correspondentes.

- **Responsividade Mobile:** A interface deve adaptar-se perfeitamente a telas de smartphones (largura alvo: 390px), sem quebras de layout, textos sobrepostos ou necessidade de barra de rolagem horizontal.

- **Responsividade Desktop:** O layout deve se comportar de forma limpa e centralizada em telas de alta resolução (largura alvo: 1440px), respeitando os limites máximos de largura definidos no mockup.

- **Console Limpo:** Ao carregar a tela e interagir com seus botões, nenhum erro (Error, ReferenceError, etc.) ou alertas esquecidos de depuração (`console.log`) devem aparecer nas ferramentas de desenvolvedor do navegador.

- **Consistência Visual Integrada:** Cores de fundo, tipografia (tamanhos e pesos de fonte) e raios de borda (`border-radius`) dos botões devem seguir estritamente o guia de estilos estipulado no mockup.

- **Navegação Funcional (Links Integros):** Todos os links internos, botões de fechar modais e redirecionamentos mútuos devem estar apontando para os arquivos corretos, mesmo que a regra de negócio lógica ainda esteja sendo simulada.

- **Acessibilidade Básica:** Inputs de formulários devem possuir tags `<label>` associadas explicitamente através do atributo `for`, e imagens essenciais devem conter o atributo `alt` preenchido de forma descritiva.

- **Validação de Código Cruzada (Peer Review):** O código da tela/componente deve ser revisado, testado localmente e aprovado por pelo menos mais um integrante do grupo antes de ser mesclado na branch principal.

- **Rastreabilidade de Histórico (Git):** O commit que finaliza a tarefa deve ser realizado com mensagens claras, descritivas e estruturadas (`feat(ui): implementa tabela responsiva de trens na tela de frotas`).
