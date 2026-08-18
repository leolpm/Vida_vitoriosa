# Planejamento do Sistema - Fluxo de Impressao Multi-evento

> Status: ativo
> Tipo: planejamento funcional e arquitetural
> Ordem de execucao: implementar depois da estrutura multi-evento por subdominio
> Dependencia arquitetural: `docs/planejamento/planejamento_multi_eventos.md`
> Fonte canonica relacionada: `docs/modulos/template-modulo.md`
> Dependencia visual: `docs/ui-standards.md`

## 1. Contexto

O sistema atual do Vida Vitoriosa deve evoluir em duas etapas relacionadas:

1. suportar mais de um evento na mesma aplicacao Laravel
2. substituir a geracao operacional de PDF por um Fluxo de Impressao controlado

A primeira expansao prevista adicionara o evento EDD sem duplicar o projeto. Os eventos serao identificados pelo dominio acessado:

| Dominio | Evento |
|---|---|
| `vidavitoriosa.atitudelaranja.com` | Vida Vitoriosa |
| `edd.atitudelaranja.com` | EDD |

Os dois dominios devem apontar para a mesma aplicacao Laravel e para o mesmo banco de dados. Participantes, depoimentos, configuracoes, arquivos e operacoes devem permanecer isolados por evento.

Este documento assume que `docs/planejamento/planejamento_multi_eventos.md` estara implementado e validado antes do inicio do modulo Fluxo de Impressao. As regras detalhadas de dominios, ambiente local, migracao e testes de isolamento pertencem ao planejamento multi-evento e nao devem ser redefinidas neste arquivo.

O modulo de Fluxo de Impressao mantera o visual aprovado do sistema, mas alterara a operacao principal para:

- distribuir fluxos para membros da equipe
- revisar cartas de um participante
- preparar a impressao
- abrir a impressao no navegador
- confirmar a conclusao
- rastrear a operacao com controle de acesso, evento e validade

### 1.1 Estado esperado antes desta implementacao

Antes de iniciar o Fluxo de Impressao, o sistema devera possuir:

- cadastro de eventos
- identificacao do evento pelo dominio da requisicao
- participantes vinculados a um evento
- depoimentos vinculados a um evento
- configuracoes separadas por evento
- imagens e arquivos organizados por evento
- dashboard, relatorios e administracao filtrados pelo evento atual
- dados atuais migrados para o evento Vida Vitoriosa
- evento EDD cadastrado com seu dominio e suas configuracoes

### 1.2 Decisoes arquiteturais congeladas

| Decisao | Regra |
|---|---|
| Aplicacao | Uma unica aplicacao Laravel atende todos os eventos |
| Banco de dados | Um unico banco com isolamento obrigatorio por evento |
| Resolucao do evento | O dominio da requisicao define o evento atual |
| Equipe | Cadastro global com autorizacao para um ou mais eventos |
| Usuarios administrativos | Cadastro global, com acesso conforme permissao |
| Limite de tarefas | Conta tarefas abertas em todos os eventos |
| Configuracoes | Separadas entre gerais e especificas do evento |
| Impressao | Realizada pela janela de impressao do navegador |
| PDF legado | Mantido para historico, sem exclusao automatica |

O evento atual nunca deve ser definido apenas por parametro de formulario, query string ou sessao. O dominio deve ser a fonte primaria do contexto.

---

## 2. Objetivo do sistema

Criar um modulo de **Fluxo de Impressao** que permita:

- cadastrar membros da equipe com nome e telefone
- autorizar membros da equipe em um ou mais eventos
- distribuir um participante do evento atual para um membro autorizado
- enviar um link via WhatsApp com acesso restrito ao fluxo daquele participante
- revisar cartas com imagens para aprovar ou reprovar
- exibir a tela de impressao com nome do autor e quantidade de paginas
- abrir a impressao no navegador
- confirmar a conclusao da impressao
- reavaliar cartas reprovadas em fluxo especifico
- identificar participantes criticos conforme a regra do evento
- controlar a quantidade de tarefas por membro da equipe
- manter rastreabilidade completa com identificacao do evento

---

## 3. Diretrizes aprovadas

- Manter o design atual do sistema nas novas telas.
- Nao alterar a linguagem visual aceita pelos usuarios.
- Seguir obrigatoriamente `docs/ui-standards.md`.
- Exibir claramente o evento atual no painel e nas telas operacionais.
- Exibir claramente a etapa atual no topo do fluxo.
- Exibir as proximas etapas no topo em estado desativado.
- Usar sempre o nome **Fluxo de Impressao**.
- Nao integrar diretamente com a API do WhatsApp nesta versao.
- Iniciar o envio pelo usuario por meio do WhatsApp Web.
- Controlar validade e limite de acesso dos links.
- Impedir qualquer mistura de dados entre eventos.
- Gerar links utilizando o dominio do evento do fluxo.
- Manter usuarios e membros da equipe como cadastros globais.

---

## 4. Escopo funcional

## 4.1 Menu principal

Depois da ativacao definitiva do novo fluxo, o menu administrativo deve conter:

- Dashboard
- Participantes
- Depoimentos
- Relatorios
- Fluxo de Impressao
- Equipe
- Configuracoes

A aba de geracao de PDF deve ser removida do menu somente depois que o Fluxo de Impressao estiver validado em producao.

Os PDFs antigos e seus registros devem permanecer acessiveis por historico administrativo, mesmo depois da remocao do item principal do menu.

## 4.2 Contexto do evento no painel

O painel deve mostrar o evento atual de forma visivel.

Exemplo:

```text
Evento atual: EDD
```

Um seletor pode permitir alternar entre eventos. A troca deve redirecionar para o dominio correspondente, e nao apenas alterar uma variavel de sessao.

Exemplo:

```text
EDD -> https://edd.atitudelaranja.com/admin/fluxo-impressao
Vida Vitoriosa -> https://vidavitoriosa.atitudelaranja.com/admin/fluxo-impressao
```

## 4.3 Aba Equipe

O cadastro de equipe sera global para evitar duplicidade de pessoas.

### Campos minimos

- nome
- telefone
- status ativo ou inativo
- eventos autorizados

### Comportamentos desejados

- validar o telefone para uso em link de WhatsApp
- padronizar o telefone em formato internacional quando possivel
- permitir ativar ou desativar o membro
- permitir vincular o membro a um ou mais eventos
- impedir atribuicao em evento para o qual o membro nao esteja autorizado
- montar a mensagem pronta para WhatsApp Web
- mostrar a quantidade de tarefas abertas em todos os eventos

### Regra de compartilhamento

Um membro pode atuar no Vida Vitoriosa, no EDD ou nos dois. O cadastro deve existir apenas uma vez.

## 4.4 Aba Fluxo de Impressao

Essa aba sera o centro operacional da solucao.

### O que deve aparecer

- fluxos do evento atual
- filtros de gestao
- status de cada fluxo
- participante vinculado
- membro responsavel
- data de distribuicao
- validade do link
- situacao do fluxo
- quantidade de acessos utilizados
- etapa atual
- indicacao de fluxo vencido, concluido, cancelado ou em revisao

### Filtros sugeridos

- participante
- membro da equipe
- status
- etapa atual
- data de distribuicao
- fluxos vencidos
- fluxos concluidos
- fluxos pendentes
- fluxos reprovados
- fluxos cancelados ou invalidados

### Regra de isolamento

A listagem deve consultar somente fluxos vinculados ao evento identificado pelo dominio atual.

## 4.5 Distribuicao de fluxo

O usuario administrativo deve conseguir distribuir um participante do evento atual para um membro autorizado.

### Fluxo esperado

1. o usuario acessa o painel pelo dominio do evento
2. o sistema identifica o evento atual
3. o usuario seleciona um participante daquele evento
4. o usuario seleciona um membro autorizado naquele evento
5. o sistema valida o limite global de tarefas do membro
6. o sistema cria o fluxo vinculado ao evento
7. o sistema gera o link no dominio correto
8. o sistema monta a mensagem para o WhatsApp Web
9. o usuario envia a mensagem manualmente
10. o membro acessa apenas o fluxo autorizado

### Regras importantes

- o link nao pode liberar acesso ao painel administrativo
- o link deve estar associado a um unico fluxo
- o fluxo deve possuir um unico participante e um evento
- o participante deve pertencer ao mesmo evento do fluxo
- o membro deve estar autorizado no evento
- o fluxo deve registrar quem realizou a distribuicao
- o sistema deve impedir uso fora da validade ou acima do limite de acessos

## 4.6 Acesso por link

O acesso externo ao fluxo sera controlado por token.

### Formato esperado

```text
https://edd.atitudelaranja.com/fluxos/{token}
https://vidavitoriosa.atitudelaranja.com/fluxos/{token}
```

### Regras propostas

- o token deve ser longo, aleatorio e armazenado de forma segura
- o valor padrao inicial de acessos permitidos deve ser `1`
- a validade padrao inicial deve ser `30 minutos`
- validade e limite podem ser configurados por evento
- cada acesso deve ser registrado
- o dominio acessado deve corresponder ao evento do fluxo
- um token acessado no dominio errado deve ser bloqueado
- o token nao deve conceder acesso geral ao sistema

### Comportamento esperado

- link valido: permite acesso ao fluxo
- link expirado: bloqueia e apresenta mensagem clara
- limite atingido: bloqueia e apresenta mensagem clara
- fluxo cancelado: bloqueia o acesso
- fluxo concluido: apresenta o estado concluido sem liberar nova operacao
- dominio incorreto: bloqueia e orienta o usuario a solicitar novo link

### Reenvio pelo WhatsApp

O sistema nao enviara mensagens automaticamente.

Quando o link expirar ou for consumido:

- o usuario administrativo gera um novo link
- o link anterior e invalidado quando aplicavel
- o sistema monta novamente a mensagem
- o usuario abre o WhatsApp Web
- o usuario envia manualmente

## 4.7 Etapas do Fluxo de Impressao

O fluxo principal deve exibir todas as etapas no topo.

### Estrutura visual

- etapa atual destacada
- etapas futuras visiveis e desativadas
- etapas concluidas identificadas
- nome do evento e participante visiveis
- navegacao simples e responsiva

### Etapa 1 - Revisao das cartas

O membro deve:

- visualizar somente cartas do participante e evento do fluxo
- visualizar imagens anexadas quando existirem
- aprovar ou reprovar cada carta
- registrar motivo da reprova quando necessario
- confirmar a conclusao da revisao

Cada decisao deve registrar membro, fluxo, evento, carta, data e resultado.

### Etapa 2 - Impressao

O sistema deve mostrar:

- identidade visual do evento
- nome do participante
- nome de quem escreveu cada depoimento
- quantidade de paginas de cada depoimento
- total de paginas do lote
- apenas cartas aprovadas para o fluxo
- botao de imprimir

### Comportamento do botao de imprimir

- abrir uma nova aba com o layout de impressao
- abrir a janela de impressao do navegador
- permitir imprimir fisicamente ou salvar como PDF pelo navegador
- manter o fluxo em andamento ate a confirmacao do operador
- nao marcar automaticamente como concluido apenas pela abertura da janela

### Etapa 3 - Confirmacao de conclusao

Depois da impressao, o membro deve confirmar:

- que o lote foi impresso
- que a tarefa foi concluida

Ao concluir:

- o fluxo recebe status concluido
- a data e o membro sao registrados
- a tarefa deixa de contar no limite de tarefas abertas
- o membro pode receber novos fluxos conforme a regra configurada

## 4.8 Fluxo de revisao das cartas reprovadas

Cartas reprovadas devem entrar em um fluxo especifico de reavaliacao no mesmo evento.

### Regras

- incluir apenas cartas reprovadas anteriormente
- manter o mesmo evento da carta original
- permitir reavaliacao por outro membro autorizado no evento
- impedir reavaliacao por membro sem acesso ao evento
- se aprovada, encaminhar a carta para impressao
- se reprovada novamente, manter o historico
- preservar todas as decisoes anteriores

## 4.9 Participantes criticos

O sistema deve identificar participantes abaixo da quantidade minima de depoimentos definida pelo evento.

### Regra

- cada evento define sua quantidade minima
- considerar apenas depoimentos do mesmo evento
- nao somar depoimentos de eventos diferentes
- o participante entra como critico quando ficar abaixo da meta

Exemplo:

| Evento | Minimo configurado |
|---|---:|
| Vida Vitoriosa | 3 |
| EDD | 2 |

### Comportamento

- permitir filtro de participantes criticos
- mostrar a quantidade atual e a meta do evento
- facilitar a criacao de tarefas para buscar novos depoimentos

## 4.10 Busca de depoimentos para participantes criticos

Esse fluxo pode usar o mesmo motor de tarefas do Fluxo de Impressao.

### Finalidade

- localizar participantes abaixo da meta
- atribuir a busca a um membro autorizado no evento
- controlar responsabilidade e conclusao
- registrar o evento da tarefa

### Operacao sugerida

1. o sistema lista participantes criticos do evento atual
2. o usuario seleciona um participante
3. o usuario atribui a tarefa a um membro autorizado
4. o limite global de tarefas e validado
5. o membro recebe a atribuicao
6. o status acompanha a execucao ate a conclusao

## 4.11 Limite de tarefas por membro

Cada membro deve possuir um limite maximo de tarefas abertas.

### Regra aprovada

O limite deve considerar, em todos os eventos:

- tarefas abertas
- tarefas em andamento
- tarefas vencidas

Uma tarefa vencida continua contando enquanto nao for concluida ou cancelada por um administrador.

Exemplo:

```text
2 tarefas abertas no Vida Vitoriosa
1 tarefa aberta no EDD
Total considerado para o membro: 3
```

## 4.12 Layout quando nao houver imagem

Quando um depoimento nao tiver imagem:

- a area reservada para imagem nao deve aparecer
- o texto deve ocupar toda a largura disponivel
- a carta deve aproveitar melhor a pagina
- o layout deve continuar associado a identidade do evento

Quando houver imagem:

- preservar proporcao e orientacao
- evitar distorcao
- respeitar margens de impressao
- manter a imagem dentro da area definida pelo layout

---

## 5. Configuracoes

As configuracoes devem ser separadas entre gerais e especificas do evento.

## 5.1 Configuracoes gerais

| Parametro | Regra inicial |
|---|---|
| Limite maximo de tarefas por membro | Configuravel |
| Expiracao do codigo administrativo | Mantida conforme configuracao atual |
| Compartilhamento de usuarios | Usuarios globais |
| Auditoria | Ativa para todos os eventos |

## 5.2 Configuracoes por evento

| Parametro | Valor inicial sugerido |
|---|---|
| Quantidade minima de depoimentos | Definida por evento |
| Validade padrao do link | 30 minutos |
| Acessos permitidos por link | 1 |
| Data de encerramento dos depoimentos | Opcional |
| Imagem do formulario | Especifica do evento |
| Imagem da impressao | Especifica do evento |
| Texto de rodape | Especifico do evento |
| Textos do formulario | Especificos do evento |
| Opcoes de relacao | Especificas do evento |

As configuracoes de um evento nao podem alterar a operacao de outro evento.

---

## 6. Modelo de dados planejado

Os nomes finais podem ser ajustados durante a implementacao, mas as relacoes e garantias abaixo sao obrigatorias.

## 6.1 Entidades de eventos

### `events`

- id
- nome
- slug
- status
- timestamps

### `event_domains`

- id
- event_id
- host
- ambiente
- esquema
- porta opcional
- dominio principal do ambiente
- status
- timestamps

Os dominios e as regras de resolucao seguem `docs/planejamento/planejamento_multi_eventos.md`.

### Dados existentes vinculados ao evento

As tabelas abaixo devem possuir `event_id`:

- participants
- testimonials
- event_settings
- pdf_batches enquanto o historico legado existir

## 6.2 Entidades da equipe

### `team_members`

- id
- nome
- telefone normalizado
- status
- limite de tarefas, se houver sobrescrita individual
- timestamps

### Associacao entre equipe e eventos

Uma associacao muitos-para-muitos deve indicar em quais eventos cada membro pode atuar.

Campos minimos:

- event_id
- team_member_id
- status da autorizacao
- timestamps

## 6.3 Entidade de fluxo

### `print_flows`

- id
- event_id
- participant_id
- team_member_id
- tipo do fluxo
- status
- etapa atual
- distribuido por usuario
- distribuido em
- concluido em
- cancelado em
- timestamps

Tipos iniciais:

- impressao principal
- reavaliacao de cartas
- busca de depoimentos

## 6.4 Tokens e acessos

O controle de acesso deve registrar:

- fluxo
- token protegido
- validade
- limite de acessos
- acessos utilizados
- data do primeiro acesso
- data do ultimo acesso
- data de invalidacao
- motivo da invalidacao

O token nao deve armazenar permissao administrativa.

## 6.5 Revisoes

Cada revisao deve registrar:

- event_id
- print_flow_id
- testimonial_id
- team_member_id
- decisao
- motivo da reprova
- data da decisao

Decisoes iniciais:

- aprovada
- reprovada
- aguardando revisao

## 6.6 Auditoria

A trilha de auditoria deve registrar:

- evento
- fluxo
- ator
- tipo do ator
- acao
- data e horario
- dados relevantes antes e depois, quando aplicavel
- endereco IP e agente do navegador quando pertinente

## 6.7 Garantias de integridade

- participante e fluxo devem pertencer ao mesmo evento
- depoimento e participante devem pertencer ao mesmo evento
- revisao, depoimento e fluxo devem pertencer ao mesmo evento
- membro deve estar autorizado no evento do fluxo
- consultas administrativas devem ser filtradas pelo evento atual
- dominio deve corresponder ao evento do token
- chaves estrangeiras e indices devem reforcar essas relacoes

---

## 7. Seguranca e controle de acesso

## 7.1 Acesso administrativo

- usuarios administrativos sao globais
- permissoes podem limitar os eventos acessiveis
- o dominio define o contexto do evento
- a sessao nao pode permitir trocar o evento sem validar o dominio
- administradores gerais podem acessar todos os eventos

## 7.2 Acesso da equipe

- link vinculado a um fluxo especifico
- token com validade
- limite de acessos configuravel
- sem acesso ao restante do sistema
- bloqueio em dominio incorreto
- bloqueio quando fluxo estiver cancelado ou invalidado

## 7.3 Auditoria obrigatoria

Registrar:

- quem distribuiu
- para qual membro foi enviado
- evento do fluxo
- quando o link foi criado
- quando foi acessado
- quando foi renovado ou invalidado
- quem aprovou ou reprovou cada carta
- quando a impressao foi aberta
- quando a conclusao foi confirmada
- cancelamentos e reavaliacoes

---

## 8. UI e experiencia de uso

As novas telas devem seguir `docs/ui-standards.md` e o design administrativo existente.

### Regras visuais

- manter tipografia, espacamento e hierarquia atuais
- reaproveitar cards, botoes, filtros, badges e modais
- identificar o evento atual no topo
- evitar cores ou componentes que criem um segundo design system
- manter responsividade no celular

### Progresso do fluxo

- destacar a etapa atual
- identificar etapas concluidas
- mostrar proximas etapas desativadas
- manter todas as etapas visiveis no topo quando houver espaco
- adaptar para navegacao compacta no celular

### Estados vazios e bloqueios

Criar mensagens claras para:

- fluxo sem cartas
- link expirado
- limite de acessos atingido
- dominio incorreto
- membro sem autorizacao no evento
- participante sem cartas aprovadas
- fluxo concluido
- fluxo cancelado

---

## 9. Dominios, hospedagem e sessao

Os dois subdominios devem apontar para a mesma aplicacao e pasta publica do Laravel.

### Requisitos

- DNS configurado para os dois dominios
- HTTPS valido nos dois dominios
- redirecionamento de HTTP para HTTPS
- proxies confiaveis configurados quando aplicavel
- URLs geradas dinamicamente conforme o evento
- evitar dependencia de `APP_URL` para imagens e links internos do evento

### Sessao administrativa

Os usuarios podem compartilhar autenticacao entre subdominios quando a infraestrutura permitir.

Configuracao esperada em producao:

```env
SESSION_DOMAIN=.atitudelaranja.com
SESSION_SECURE_COOKIE=true
```

Mesmo com sessao compartilhada, o evento deve continuar sendo resolvido pelo dominio em cada requisicao.

---

## 10. Transicao da geracao de PDF atual

O sistema atual possui geracao e historico de PDFs. A migracao para o novo fluxo deve ser gradual.

### Regras de transicao

- associar lotes atuais ao evento Vida Vitoriosa
- preservar arquivos existentes
- manter downloads historicos disponiveis
- implementar e validar a impressao pelo navegador
- testar o novo fluxo nos dois eventos
- desativar a criacao de novos PDFs somente depois da validacao
- remover o item de PDF do menu somente na ativacao definitiva
- nao apagar dados ou arquivos antigos automaticamente

O reset administrativo do sistema deve ser revisto antes da ativacao multi-evento para impedir exclusao acidental de dados de outro evento.

---

## 11. Itens fora de escopo

Nao fazem parte desta versao:

- envio automatico pela API oficial do WhatsApp
- prazo maximo por tarefa
- prioridade entre participantes criticos
- numero maximo de reavaliacoes
- tempo de expiracao de fluxo pendente
- metricas de produtividade por membro
- aplicacoes Laravel separadas para cada evento
- bancos de dados separados por evento

---

## 12. Modelo operacional resumido

1. acessar o painel pelo dominio do evento
2. identificar o evento atual
3. cadastrar ou selecionar membro da equipe autorizado
4. configurar parametros do evento
5. identificar participantes criticos do evento
6. selecionar um participante do evento atual
7. distribuir o fluxo
8. validar o limite global de tarefas do membro
9. gerar link no dominio correto
10. enviar manualmente pelo WhatsApp Web
11. acessar o fluxo restrito
12. revisar cartas e aprovar ou reprovar
13. encaminhar reprovadas para reavaliacao quando necessario
14. visualizar a etapa de impressao
15. abrir a impressao do navegador
16. confirmar a conclusao
17. liberar capacidade do membro para novos fluxos

---

## 13. Preparacao de dados de teste

O ambiente de teste deve conter dados coerentes nos dois eventos.

### Eventos

- Vida Vitoriosa
- EDD

### Dados necessarios

- participantes de cada evento
- participantes com nomes semelhantes em eventos diferentes
- membros autorizados em apenas um evento
- membros autorizados nos dois eventos
- depoimentos com imagem e sem imagem
- cartas aprovadas e reprovadas
- fluxos distribuidos
- links validos, expirados e consumidos
- tarefas abertas, vencidas e concluidas
- participantes abaixo e acima da meta de cada evento
- PDFs antigos associados ao Vida Vitoriosa

### Objetivo

- validar isolamento entre eventos
- validar revisao e impressao
- validar expiracao e limite dos links
- validar o limite global de tarefas
- evitar inconsistencias com dados do modelo antigo

---

## 14. Estrategia de testes

## 14.1 Testes de isolamento

- participante do EDD nao aparece no Vida Vitoriosa
- participante do Vida Vitoriosa nao aparece no EDD
- depoimento nao pode entrar em fluxo de outro evento
- configuracao de um evento nao altera outro
- token nao funciona em dominio diferente
- relatorio e dashboard respeitam o evento atual

## 14.2 Testes de permissao

- membro sem autorizacao nao recebe fluxo
- link nao libera painel administrativo
- administrador limitado nao acessa evento nao autorizado
- token expirado ou consumido e bloqueado

## 14.3 Testes funcionais

- distribuir fluxo
- gerar link
- revisar cartas
- reprovar com motivo
- reavaliar carta
- abrir impressao
- confirmar conclusao
- calcular participantes criticos
- controlar limite de tarefas entre eventos

## 14.4 Testes de impressao

- carta com imagem vertical
- carta com imagem horizontal
- carta sem imagem
- texto curto
- texto longo e multipagina
- emojis
- margens e rodape
- identidade correta do evento
- contagem de paginas

---

## 15. Criterios de aceite

O planejamento sera considerado atendido quando o sistema:

- possuir a estrutura multi-evento validada
- identificar o evento pelo dominio
- impedir mistura de participantes e depoimentos
- exibir o evento atual no painel
- incluir as abas Fluxo de Impressao e Equipe
- cadastrar membros com nome e telefone
- autorizar membros por evento
- distribuir fluxos apenas dentro do evento atual
- listar e filtrar fluxos do evento
- controlar validade e quantidade de acessos
- gerar links no dominio correto
- bloquear token em dominio incorreto
- impedir acesso fora do fluxo autorizado
- mostrar etapa atual e proximas etapas
- aprovar ou reprovar cartas com rastreio
- mostrar nome do autor e quantidade de paginas
- usar a identidade visual do evento na impressao
- abrir a impressao do navegador
- confirmar conclusao
- reavaliar cartas reprovadas no mesmo evento
- identificar participantes criticos pela configuracao do evento
- respeitar o limite global de tarefas
- ocultar a area de imagem quando nao houver imagem
- preservar historico de PDFs antigos
- manter auditoria com identificacao do evento
- possuir testes de isolamento entre EDD e Vida Vitoriosa

---

## 16. Prioridade de implementacao

### Fase 0 - Estrutura multi-evento obrigatoria

- criar eventos e dominios
- resolver evento pelo host
- migrar dados atuais para Vida Vitoriosa
- cadastrar EDD
- vincular participantes e depoimentos
- separar configuracoes e arquivos
- adaptar dashboard, relatorios e administracao
- implementar formulario do EDD
- criar testes de isolamento

Esta fase deve ser concluida antes do Fluxo de Impressao.

### Fase 1 - Base operacional

- ajustar menu
- cadastrar equipe global
- autorizar equipe por evento
- criar configuracoes gerais e por evento
- distribuir fluxo
- listar e filtrar fluxos

### Fase 2 - Execucao

- gerar acesso por link
- validar dominio, validade e limite
- revisar cartas
- abrir impressao no navegador
- confirmar conclusao

### Fase 3 - Gestao avancada

- reavaliar cartas reprovadas
- identificar participantes criticos por evento
- criar tarefas de busca de depoimentos
- controlar limite global de tarefas

### Fase 4 - Refinamentos e transicao

- ajustar layout sem imagem
- concluir auditoria
- validar impressao nos dois eventos
- preservar historico de PDF
- desativar geracao de novos PDFs
- remover item de PDF do menu

---

## 17. Observacao final

Este planejamento consolida duas evolucoes complementares:

- o sistema deixa de ser exclusivo do Vida Vitoriosa e passa a atender eventos por subdominio
- a operacao deixa de depender da geracao administrativa de PDF e passa a usar revisao, distribuicao, impressao no navegador e confirmacao

A arquitetura multi-evento e um pre-requisito. O Fluxo de Impressao deve ser construido uma unica vez sobre essa base, funcionando para Vida Vitoriosa, EDD e futuros eventos sem duplicacao de codigo.

---

## 18. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Atualizacao integral do planejamento para arquitetura multi-evento por subdominio, com isolamento de dados, equipe compartilhada, links por evento, configuracoes, migracao e nova Fase 0 | Preparar o Fluxo de Impressao para funcionar no Vida Vitoriosa, EDD e eventos futuros sem retrabalho ou mistura de dados |
| 2026-08-18 | Referencia ao planejamento multi-evento e substituicao do dominio unico pela entidade `event_domains` | Manter uma unica fonte para ambiente local, resolucao de dominios, migracao e testes multi-evento |
