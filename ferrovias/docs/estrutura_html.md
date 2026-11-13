# Guia da estrutura HTML (para fazer o CSS)

O HTML esta dentro dos proprios arquivos PHP, sem estilos. Todas as telas usam o mesmo "casco" (`includes/cabecalho.php` e `rodape.php`) e as mesmas classes, entao um CSS so cobre o sistema inteiro. O arquivo a editar e `assets/css/estilo.css` (hoje so tem as cores semanticas e a classe `.visualmente-oculto`).

## 1. Casco da pagina (todas as telas internas)
```
body.pagina.pagina-<nome-do-arquivo>
 └ div.app
    ├ header.topo
    │   ├ div.topo-esquerda  →  a.botao-voltar (opcional)  +  a.marca (img.marca-logo + span.marca-nome)
    │   └ div.topo-direita   →  p.usuario-logado (.usuario-nome, .usuario-papel)  +  a.botao-alertas (sino)
    ├ nav.navegacao
    │   └ ul.navegacao-lista
    │       ├ li.navegacao-item > a.navegacao-link (icone + span.navegacao-texto)   [Inicio, Frota, Relatorios]
    │       └ li.navegacao-item.navegacao-mais > details.menu-mais > summary.navegacao-link + ul.menu-mais-lista
    └ main.conteudo   (o conteudo de cada tela)
```
- A mesma `nav` serve de **barra inferior no celular** e de **menu lateral no desktop**: so CSS (`position: fixed; bottom: 0` no celular, grid ou coluna no desktop).
- O item ativo do menu tem `aria-current="page"`: estilize com `.navegacao-link[aria-current="page"]` (no design, o icone ativo e azul).
- O "Mais" e um `<details>`: abre e fecha sem JavaScript.
- O menu muda por perfil (Funcionario ve so Inicio e Mais; Maquinista tem Frota apontando para Sensores; Gestor ve tudo). Nao precisa tratar isso no CSS.
- Cada pagina tem uma classe propria no `body`: `pagina-trens`, `pagina-trens_form`, `pagina-dashboard` etc. Use para ajustes especificos.

Telas publicas (login e recuperar senha) usam `body.pagina.pagina-publica` > `main.tela-entrada` > `header.entrada-marca` (logo + nome) + `section.cartao-formulario`. Nao tem `header.topo` nem `nav`.

## 2. Dicionario de classes
| Componente | Classes |
|---|---|
| Titulo da tela | `section.pagina-cabecalho` > `h1.titulo-pagina` (+ `p.subtitulo`) |
| Titulo de secao | `h2.subtitulo-secao` |
| Botoes | `.botao` + `.botao-primario` (azul), `.botao-secundario`, `.botao-editar` (amarelo no design), `.botao-perigo` (vermelho), `.botao-largo` (largura total). Link simples: `.botao-link` |
| Barra de acoes no fim da tela | `div.acoes-pagina` (o botao "Adicionar" do design) |
| Mensagens | `div.mensagens` > `p.mensagem.mensagem-sucesso` ou `.mensagem-erro` |
| Lista vazia | `p.estado-vazio` |
| Selos de status | `span.selo` + `.status-normal` (verde), `.status-atencao` (amarelo), `.status-critico` (vermelho). Alertas usam `.sev-normal/.sev-atencao/.sev-critico`. Rotas: `.selo-rota-programada/em_andamento/concluida/cancelada` |
| Linha destacada | `.linha-critica` (alerta critico), `.linha-inativa` (usuario inativo) |
| Cargos | `.cargo-administrador` (Gestor), `.cargo-maquinista`, `.cargo-usuario_comum` (Funcionario); no design: Funcionario azul, Maquinista amarelo |
| Avatar do usuario | `span.avatar` dentro da celula do nome |
| Paginacao | `nav.paginacao` > `a.paginacao-anterior`, `span.paginacao-atual`, `a.paginacao-proxima` |
| Indicadores do painel | `ul.indicadores` > `li.indicador` (+ `.status-*` ou `.sev-*`) > `.indicador-valor` + `.indicador-rotulo` |
| Secoes do painel | `section.painel-secao` (+ `.painel-frota`, `.painel-monitoramento`, `.painel-criticos`, `.painel-alertas`, `.painel-viagens`) |
| Resumo (editar trem e relatorio) | `section.resumo-trem` / `section.relatorio-filtros` > `dl.resumo-lista` > `div.resumo-item` > `dt` + `dd` |
| Codigo de recuperacao | `div.codigo-recuperacao` (`.codigo-rotulo`, `.codigo-valor`) |

## 3. Tabela no desktop, cartao no celular (mesmo HTML)
Todas as listas sao `table.tabela.tabela-cartoes`. Cada `<td>` tem `data-label="Nome da coluna"`. No celular, transforme a tabela em cartoes e mostre o rotulo com o proprio `data-label`:
```css
@media (max-width: 767px) {
  .tabela-cartoes thead { position: absolute; width: 1px; height: 1px; overflow: hidden; }
  .tabela-cartoes, .tabela-cartoes tbody, .tabela-cartoes tr, .tabela-cartoes td { display: block; }
  .tabela-cartoes tr.linha { /* o cartao: fundo, borda, raio, margem */ }
  .tabela-cartoes td::before { content: attr(data-label); /* rotulo pequeno */ }
}
```
- `td.celula-principal` e o titulo do cartao (nome do usuario, prefixo do trem, codigo do sensor...). No design, e o texto grande.
- `td.celula-acoes` guarda os botoes (Editar / Desativar). No design ficam a direita do cartao.
- Os formularios de exclusao dentro da celula tem `form.form-linha`: coloque `display: inline` ou `contents`.

## 4. Formularios
```
form.formulario[.formulario-trem | -sensor | -rota | -usuario | -simulador | -relatorio]
 ├ fieldset.grupo[.grupo-identificacao | -operacional | ...] > legend.grupo-titulo
 │    └ div.campo > label.campo-rotulo + input|select.campo-entrada (+ small.campo-ajuda)
 └ div.formulario-acoes > button.botao.botao-primario.botao-largo
```
- Os agrupamentos seguem as secoes do design ("Identificacao", "Especificacoes", "Operacional"...).
- **Status e Cargo sao grupos de opcoes** (como os chips "Ativo / Manutencao / Parado" do design): `div.opcoes` > `label.opcao` > `input[type=radio]` + `span.opcao-texto`. Esconda o radio e estilize o `span` usando `input:checked + .opcao-texto`. Na tela de trens, cada `label.opcao` ja traz a cor (`.status-normal/.status-atencao/.status-critico`).
- Filtros e busca de cada lista: `form.filtros` > `div.campo.campo-busca` (barra de busca, `input[type=search]`), `div.campo` (selects) e `div.filtros-acoes`. Os rotulos de busca sao `.visualmente-oculto` (existem para leitores de tela).

## 5. Icones
Os icones sao `span.icone.icone-<nome>` vazios (`aria-hidden`): desenhe com `background-image`, `mask` ou SVG no CSS. Nomes usados: `inicio`, `frota`, `relatorios`, `mais`, `sino`, `voltar`, `status-em_operacao`, `status-manutencao`, `status-inativo`. No design, o de status "Parado" e o circulo vermelho com pausa, "Manutencao" a chave amarela.

## 6. Tokens do design (Design Infos)
| Papel | Valor |
|---|---|
| Fundo primario / secundario | `#1E2126` / `#23272E` |
| Cards e superficies / bordas | `#2B3138` / `#343B44` |
| Texto primario / secundario / terciario | `#E6E8EB` / `#A6ADB5` / `#737A84` |
| Azul (acao) | `#3A7AFE` |
| Vermelho / amarelo / verde (status) | `#E05A5A` / `#E0B341` / `#4CD964` |
| Fonte | Inter: titulos 26 semibold, subtitulos 20 semibold, textos importantes 16 semibold, texto 14 regular, legenda 12 regular |

Sugestao: declare como variaveis em `:root` e substitua as cores fixas das classes `.status-*`, `.sev-*` e `.mensagem-*` em `estilo.css`.

## 7. O que o design tem e o sistema (ainda) nao tem
Estes itens aparecem nas telas do Figma, mas **nao existem no banco nem no PHP**. Ficaram de fora do HTML de proposito, para nao inventar funcionalidade (o guia de fechamento pede para nao construir nada novo):

| No design | Situacao no sistema |
|---|---|
| Tela de abertura com "Criar Conta" e tela "Criar Conta" (e-mail, nome, senha, "Entrar com Google") | Nao ha cadastro publico: so o gestor cadastra usuarios (regra do guia do bloco 1). Login e por **login + senha**, nao e-mail. Sem login com Google. |
| E-mail do usuario (busca por e-mail, cartoes, formularios) | A tabela `usuarios` nao tem e-mail; os cartoes mostram o login. |
| Cargo "Funcionario" / "Gestor" | Existem como nomes na tela: Funcionario = `usuario_comum`, Gestor = `administrador`. |
| Status do usuario "Operando / Parado" e "Dados do Maquinista" (trem e linha) | Nao existem. O sistema tem so ativo/inativo, controlado por Desativar/Reativar. |
| Visualizar usuario (horas trabalhadas, tarefas) | Nao existe. |
| Cadastro de trem com Categoria (Cargueiro/Passageiro), Carga (tipo e material), Linha, Terminal, Maquinista e capacidade em pessoas | O trem tem prefixo (ID), modelo, ano, status, capacidade (t) e ultima inspecao. |
| Editar trem: velocidade atual e ultima atualizacao | **Existem**: vem da leitura mais recente do trem. A "Localizacao" nao existe. |
| Botao "Parar" ao lado da busca (lista de usuarios) | Nao identificado; ficou de fora. |
| Tela de Frota, Relatorios e Mais como telas separadas | Frota = lista de trens; Relatorios = relatorio de alertas; Mais = menu com as demais telas. |
