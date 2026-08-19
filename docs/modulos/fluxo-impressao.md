# Modulo Fluxo de Impressao

## 1. Visao geral

O modulo substitui gradualmente a geracao operacional de PDFs por um fluxo rastreavel de distribuicao, revisao, impressao no navegador e confirmacao. A implementacao atende Vida Vitoriosa e EDD sobre a arquitetura multi-evento da v2.

O PDF legado permanece disponivel no menu como `PDFs legados` ate a validacao e a transicao operacional em producao.

## 2. Fluxos

### Impressao principal

1. o administrador seleciona o tipo da tarefa
2. o sistema lista apenas participantes e cartas elegiveis no evento atual
3. o administrador seleciona o participante e as cartas desejadas
4. o sistema lista apenas membros autorizados que ainda possuam vaga no limite global
5. a elegibilidade e recalculada dentro de uma transacao antes da gravacao
6. um token temporario e gerado no dominio do evento
7. o administrador e redirecionado para a pagina exclusiva de compartilhamento
8. o administrador copia o link ou abre a mensagem pronta no WhatsApp Web
9. o membro revisa cada carta e registra aprovacao ou reprovacao
10. o sistema calcula a quantidade de paginas das cartas aprovadas
11. o membro abre a impressao do navegador
12. o fluxo somente e concluido depois da confirmacao explicita

Uma carta e elegivel para impressao principal quando possui aprovacao administrativa, nao esta arquivada, nunca recebeu decisao no Fluxo de Impressao e nao pertence a outro fluxo aberto.

### Reavaliacao

Um fluxo de reavaliacao recebe cartas reprovadas e preserva as decisoes anteriores. A nova decisao e adicionada ao historico, sem substituir o registro anterior.

A fila automatica inclui apenas cartas aguardando a primeira reavaliacao. Uma carta ja reavaliada e ainda reprovada sai do indicador automatico, recebe a marcacao `Ja reavaliada N vez(es)` e continua disponivel para redistribuicao manual com a opcao de incluir reavaliadas.

### Busca de depoimentos

Participantes abaixo do minimo configurado por evento podem ser distribuidos como tarefa de busca. O membro confirma manualmente que realizou a busca e informou a equipe.

## 3. Regras de negocio

- equipe e cadastro global; a autorizacao de atuacao e por evento
- participante, depoimento, fluxo, token, revisao e auditoria devem pertencer ao mesmo evento
- o limite de tarefas abertas de um membro considera todos os eventos
- tarefas vencidas continuam ocupando o limite ate conclusao ou cancelamento
- o token e armazenado somente como hash
- o acesso exige dominio do evento, validade, limite de acessos e fluxo ativo
- uma sessao valida consome um acesso apenas na primeira abertura
- abrir a pagina de impressao nao conclui o fluxo
- a conclusao exige confirmacao explicita do membro
- reprovacao exige motivo
- revisoes formam historico imutavel de decisoes
- cada carta apresenta total de revisoes, total de reavaliacoes, ultima decisao, ultimo revisor e historico completo
- a decisao mais recente dentro do fluxo determina se a carta segue para impressao
- imagens preservam proporcao e orientacao; cartas sem imagem usam toda a largura
- a composicao de paginas e deterministica para permitir contagem antes da impressao

Estados iniciais:

- `distributed`
- `in_review`
- `ready_to_print`
- `printing`
- `completed`
- `cancelled`

Tipos iniciais:

- `main_print`
- `reevaluation`
- `testimonial_search`

## 4. Permissoes

- administradores autenticados distribuem, renovam e cancelam fluxos do evento atual
- somente membros ativos e autorizados no evento podem receber tarefas
- o link externo concede acesso apenas ao fluxo associado ao token
- o link externo nunca concede acesso ao painel administrativo
- model binding e escopos de evento impedem operacao cruzada entre dominios

## 5. Modelo de dados

| Entidade | Responsabilidade |
|---|---|
| `team_members` | Cadastro global da equipe e limite individual opcional |
| `event_team_member` | Autorizacao do membro em cada evento |
| `print_flows` | Tarefa, participante, responsavel, estado e etapa |
| `print_flow_testimonial` | Cartas que compoem o fluxo |
| `print_flow_tokens` | Hash, validade, limite e consumo do acesso |
| `print_flow_reviews` | Historico das decisoes por carta |
| `print_flow_audits` | Trilha de criacao, transicoes e conclusao |

## 6. Rotas e endpoints

Rotas administrativas:

```text
/admin/team
/admin/print-flows
/admin/print-flows/create
/admin/print-flows/distribution-options
/admin/print-flows/{printFlow}/share
/admin/print-flows/{printFlow}/renew
/admin/print-flows/{printFlow}/cancel
```

Rotas externas:

```text
/fluxos/{token}
/fluxos/{token}/cartas/{testimonial}
/fluxos/{token}/concluir-revisao
/fluxos/{token}/imprimir
/fluxos/{token}/concluir
/fluxos/{token}/concluir-busca
```

Os links usam o host do evento. Em ambiente local:

```text
http://vidavitoriosa.atitudelaranja.test:8888/fluxos/{token}
http://edd.atitudelaranja.test:8888/fluxos/{token}
```

## 7. UI

O painel recebeu as areas `Fluxo de Impressao` e `Equipe`, mantendo o design administrativo existente. A listagem do Fluxo de Impressao mostra tres cartoes operacionais:

- participantes abaixo da meta
- participantes com cartas elegiveis para impressao principal
- participantes com cartas aguardando primeira reavaliacao

A mesma tela permite filtrar tarefas por um ou varios status. Os status usam regra `OU` entre si e regra `E` com participante, membro, tipo e vencimento. A selecao permanece na URL e na paginacao.

A distribuicao e vertical e dinamica: tipo, participantes, cartas, membro e resumo. A pesquisa filtra participantes pelo nome, todas as cartas elegiveis iniciam selecionadas e podem ser removidas individualmente. Membros sem vaga nao aparecem.

A pagina de compartilhamento mostra o token original somente na primeira abertura depois da criacao ou renovacao. Depois disso, oferece a geracao de um novo link, preservando o armazenamento somente como hash.

As configuracoes incluem limite global, validade, acessos por link e minimo de depoimentos do evento.

O portal externo exibe:

- identidade cromatica do evento
- participante e membro responsavel
- etapas atuais e futuras
- cartas, autores, relacoes, imagens e decisoes
- resumo de paginas antes da impressao
- confirmacao de conclusao separada da abertura da impressao

Vida Vitoriosa usa a identidade dourada existente. EDD usa azul. O layout e responsivo e empilha etapas, conteudo, imagens e acoes em telas pequenas.

## 8. Erros e excecoes

- token inexistente, expirado, consumido ou invalidado: acesso bloqueado com mensagem amigavel
- host diferente do evento: acesso bloqueado
- membro inativo ou sem autorizacao: distribuicao rejeitada
- limite global atingido: distribuicao rejeitada
- carta fora da elegibilidade, de outro participante ou ja atribuida: distribuicao rejeitada
- nenhuma carta selecionada em tarefa de revisao: distribuicao rejeitada
- participante ou carta de outro evento: operacao rejeitada
- reprovacao sem motivo: erro de validacao
- revisao incompleta: impressao bloqueada
- fluxo cancelado: operacoes externas bloqueadas

## 9. Auditoria

A tabela `print_flow_audits` registra evento, fluxo, acao, ator, contexto e data. Sao auditados distribuicao, renovacao e invalidacao de token, revisoes, mudancas de etapa, abertura da impressao, conclusao e cancelamento.

## 10. Testes

Cobertura automatizada principal:

- distribuicao autorizada e rejeicao de membro sem permissao
- limite global de tarefas entre eventos
- token vinculado ao evento, consumo por sessao e expiracao
- revisao com motivo obrigatorio e historico preservado
- selecao parcial das cartas e rejeicao de identificadores adulterados
- candidatos e cartoes calculados pelas mesmas regras de elegibilidade
- membro no limite removido das opcoes e rejeitado novamente pelo backend
- reavaliacao repetida fora da fila automatica e disponivel pela fila manual
- filtro com um ou varios status combinado com participante
- link original disponivel uma unica vez e renovacao com invalidacao do anterior
- impressao sem conclusao automatica
- confirmacao final e liberacao da capacidade do membro
- composicao deterministica de paginas para texto curto, longo e sem imagem
- dados de demonstracao idempotentes nos dois eventos

Validacao visual local executada:

- revisao Vida Vitoriosa em desktop e celular
- listagem com tres cartoes no Vida Vitoriosa e no EDD
- distribuicao vertical com selecao de cartas em desktop e celular
- filtro multisselecao de status em celular
- pagina sem sobreposicao do resumo em janelas de menor altura
- imagens verticais e horizontais sem distorcao
- emojis no texto das cartas
- tarefa de busca EDD em desktop
- identidade separada por evento
- ausencia de erros de console nas paginas validas

A tela administrativa redirecionou corretamente para o login por codigo. A inspecao visual autenticada do painel deve ser repetida manualmente antes da release; os endpoints administrativos permanecem cobertos pela suite automatizada.

Dados locais de demonstracao:

```powershell
php artisan db:seed --class=PrintFlowDemoSeeder
```

Links conhecidos do seeder:

```text
http://vidavitoriosa.atitudelaranja.test:8888/fluxos/demo-vida-vitoriosa-fluxo-impressao
http://edd.atitudelaranja.test:8888/fluxos/demo-edd-fluxo-impressao
```

## 11. Pendencias

- executar validacao visual autenticada das telas administrativas
- validar a janela de impressao nos navegadores usados pela operacao
- homologar o calculo de paginas com os modelos reais de cartas dos dois eventos
- publicar a branch e executar migrations somente apos aprovacao
- criar a tag `v3.0.0` apenas depois da homologacao e da implantacao aprovada
- remover o PDF legado do menu somente depois da transicao operacional

## 12. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao da documentacao canonica do Fluxo de Impressao implementado localmente | Registrar regras, dados, rotas, operacao, testes e pendencias da v3.0.0 |
| 2026-08-18 | Implementacao da distribuicao dinamica, cartoes operacionais, filtro multiplo, historico de reavaliacoes e pagina de compartilhamento | Alinhar a operacao administrativa aos candidatos elegiveis e melhorar a rastreabilidade das tarefas |
