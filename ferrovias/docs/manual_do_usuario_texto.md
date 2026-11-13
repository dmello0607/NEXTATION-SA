# Manual do Usuario: Nextation: Sistema de Monitoramento Ferroviario

> Texto-base. Para virar o `manual-do-usuario.pdf` do repositorio: cole em um documento (Word ou Google Docs), tire as capturas de tela no sistema rodando (substituindo cada marcacao [CAPTURA]) e exporte como PDF. Sem codigo, sem banco de dados, sem instalacao: isso e do README.

## 1. Apresentacao
O Sistema de Monitoramento Ferroviario ajuda a acompanhar os trens, os sensores instalados neles e os avisos de problema. Ele mostra quando um sensor registra algo fora do normal, para que a equipe possa agir. Tambem permite organizar as viagens programadas e emitir relatorios.

## 2. Como acessar
1. Abra o navegador e digite o endereco do sistema: [PREENCHER: endereco, por exemplo http://localhost/ferrovias].
2. Digite o seu **login** e a sua **senha**.
3. Clique em **Entrar**.

[CAPTURA: tela de login]

Se digitar errado, o sistema avisa. Para sair, clique em **Sair** no menu do topo.

## 3. Os perfis
| Perfil | O que pode fazer |
|---|---|
| Funcionario | Ver o painel com a situacao da frota e as proximas viagens. |
| Maquinista | Tudo do usuario comum, mais consultar sensores, leituras, alertas e rotas. Nao cadastra nem exclui. |
| Gestor | Acesso total: trens, sensores, rotas, simulador, usuarios e relatorios. |

O menu do topo mostra apenas o que o seu perfil permite.

## 4. Tarefas principais

### 4.1 Cadastrar um trem (gestor)
1. No menu, clique em **Trens**.
2. Clique em **Cadastrar novo trem**.
3. Preencha prefixo (por exemplo TR-204), modelo, ano, status e capacidade em toneladas.
4. Clique em **Salvar**.

[CAPTURA: lista de trens] [CAPTURA: formulario de cadastro]

### 4.2 Acompanhar os alertas (gestor e maquinista)
1. No menu, clique em **Alertas**.
2. Para ver so os mais graves, escolha **Critico** em "Todas as severidades" e clique em **Filtrar**.
3. Os alertas criticos aparecem destacados em vermelho. Cada linha mostra o trem, o sensor e a mensagem.

[CAPTURA: central de alertas com filtro]

### 4.3 Programar uma viagem (gestor)
1. No menu, clique em **Rotas**.
2. Clique em **Cadastrar nova rota**.
3. Escolha o trem, informe origem, destino, data e horario de partida.
4. Clique em **Salvar**. Se o trem ja tiver outra viagem no mesmo horario, o sistema avisa.

[CAPTURA: lista de rotas] [CAPTURA: formulario de rota]

### 4.4 Gerar um relatorio de alertas (gestor)
1. No menu, clique em **Relatorios**.
2. Escolha o trem (ou todos), o periodo e a severidade.
3. Clique em **Gerar relatorio**.
4. Para guardar ou imprimir, clique em **Imprimir / salvar em PDF**.

[CAPTURA: formulario do relatorio] [CAPTURA: relatorio gerado]

## 5. Perguntas frequentes
**Esqueci minha senha. O que faco?**
Na tela de login, clique em "Esqueci minha senha", informe o seu login e copie o codigo que aparece. Depois digite o codigo e escolha uma nova senha. O codigo vale por 30 minutos.

**O sistema me desconectou sozinho. Por que?**
Isso acontece quando o seu acesso foi desativado por um gestor ou quando a sessao expirou. Entre novamente; se continuar, fale com um gestor.

**Nao consigo excluir um trem. Por que?**
Trens que ja tem sensores ou viagens cadastrados nao podem ser excluidos, para nao perder o historico. Se o trem nao esta mais em uso, edite-o e mude o status para "Parado".

**Nao vejo o menu "Usuarios" nem "Trens". Por que?**
Essas telas sao exclusivas do gestor. Se voce precisa de acesso, peca a um gestor.
