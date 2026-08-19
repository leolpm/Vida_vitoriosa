# Planejamento da Melhoria de Distribuicao e Revisao dos Fluxos

> Status: ativo e aguardando implementacao
> Tipo: evolucao funcional do Fluxo de Impressao
> Fonte canonica relacionada: `docs/modulos/fluxo-impressao.md`
> Planejamento-base: `docs/planejamento/planejamento_fluxo_impressao.md`
> Dependencia visual: `docs/ui-standards.md`

## 1. Objetivo

Melhorar a distribuicao de tarefas da equipe para que o administrador visualize somente participantes, cartas e membros realmente elegiveis para cada tipo de fluxo.

A evolucao tambem deve tornar a tela principal do Fluxo de Impressao um painel operacional, com indicadores para:

- participantes abaixo da meta de depoimentos
- participantes com cartas disponiveis para impressao principal
- participantes com cartas aguardando a primeira reavaliacao

O historico de revisoes deve permanecer completo. O sistema deve identificar quantas vezes cada carta foi revisada, quem realizou cada decisao e quando ela ocorreu.

---

## 2. Escopo da melhoria

Esta melhoria abrange:

- reorganizacao da tela `Distribuir nova tarefa`
- carregamento dinamico dos candidatos conforme o tipo de tarefa
- selecao individual das cartas que formarao o fluxo
- filtro de membros conforme autorizacao e limite de tarefas
- pagina exclusiva para compartilhar o novo fluxo
- novos cartoes operacionais na listagem dos fluxos
- marcacao visual das cartas ja revisadas ou reavaliadas
- contador e historico dos revisores de cada carta
- testes automatizados e visuais dos novos comportamentos

Nao fazem parte desta melhoria:

- envio automatico pelo WhatsApp
- integracao com a API oficial do WhatsApp
- remocao do historico de revisoes
- mistura de dados entre eventos
- alteracao da regra de resolucao do evento pelo dominio

---

## 3. Organizacao da tela de distribuicao

Os campos nao devem permanecer lado a lado como na versao inicial. A tela deve seguir esta ordem vertical:

1. tipo da tarefa
2. relacao de participantes elegiveis
3. relacao de cartas elegiveis, quando o tipo possuir cartas
4. membro responsavel disponivel
5. resumo da distribuicao
6. botao `Distribuir fluxo`

Em telas grandes, apenas informacoes internas da mesma secao podem usar colunas. Em celulares, todo o conteudo deve permanecer empilhado.

### 3.1 Pesquisa de participantes

A relacao deve possuir um campo de pesquisa por nome. A pesquisa deve filtrar somente os participantes elegiveis para o tipo selecionado.

Ao trocar o tipo da tarefa, o sistema deve:

- limpar participante e cartas selecionados anteriormente
- carregar novamente os candidatos do tipo atual
- atualizar a lista de membros disponiveis
- atualizar o resumo antes de permitir a distribuicao

Devem existir estados visuais para carregamento, lista vazia e falha de carregamento.

---

## 4. Elegibilidade por tipo de tarefa

## 4.1 Impressao principal

A lista deve mostrar somente participantes ativos que possuam pelo menos uma carta elegivel para um novo fluxo de impressao principal.

Uma carta sera elegivel quando:

- pertencer ao participante e ao evento atuais
- possuir status administrativo aprovado
- nao estiver arquivada
- ainda nao possuir decisao no Fluxo de Impressao
- nao estiver vinculada a outro fluxo aberto

Cada participante deve mostrar a quantidade de cartas elegiveis.

Depois da escolha do participante, as cartas devem aparecer abaixo em uma lista de checkboxes. Todas devem iniciar selecionadas, e o administrador pode remover cartas individualmente antes de distribuir.

As cartas removidas da selecao continuam disponiveis para uma distribuicao futura.

## 4.2 Reavaliacao de cartas

A lista automatica deve mostrar somente participantes com cartas cuja decisao mais recente seja `reprovada` e que ainda aguardem a primeira reavaliacao.

Nao devem entrar na fila automatica:

- cartas que nunca receberam decisao
- cartas cuja decisao mais recente seja aprovada
- cartas vinculadas a outro fluxo aberto
- cartas que ja passaram por uma reavaliacao concluida

Cada carta deve mostrar:

- nome de quem escreveu
- relacao com o participante
- data do depoimento
- motivo da ultima reprovacao
- ultima decisao
- nome do ultimo revisor
- data da ultima revisao
- quantidade total de revisoes
- quantidade de reavaliacoes

Todas as cartas elegiveis devem iniciar selecionadas, com possibilidade de remocao individual.

### Reavaliacao manual de uma carta ja reavaliada

Uma carta que passou por uma reavaliacao e continua reprovada deve sair da contagem automatica do cartao `Participantes com cartas para revisao`.

Ela deve receber a marcacao visual:

```text
Ja reavaliada 1 vez
```

ou:

```text
Ja reavaliada 3 vezes
```

Mesmo fora da fila automatica, a carta deve continuar disponivel em uma secao ou filtro chamado `Reavaliadas e ainda reprovadas`. Nessa lista, o administrador pode selecionar `Reavaliar novamente` e criar outra tarefa manualmente.

Uma carta aprovada na decisao mais recente nao pode receber nova tarefa de reavaliacao comum.

## 4.3 Busca de depoimentos

A lista deve mostrar somente participantes ativos cuja quantidade de depoimentos nao arquivados esteja abaixo da meta configurada para o evento.

Cada participante deve mostrar:

```text
Quantidade atual / Meta do evento
```

Participantes que ja possuam uma tarefa aberta de busca de depoimentos nao devem ser oferecidos para uma nova distribuicao do mesmo tipo.

Esse tipo de tarefa nao possui selecao de cartas.

---

## 5. Cartoes operacionais na listagem de fluxos

Acima da relacao de tarefas distribuidas devem aparecer tres cartoes, todos limitados ao evento atual.

## 5.1 Participantes abaixo da meta

Mostra a quantidade de participantes ativos abaixo da meta de depoimentos configurada para o evento.

O cartao deve apresentar:

- quantidade de participantes abaixo da meta
- meta atual do evento
- quantidade que ja possui tarefa de busca aberta, como informacao secundaria
- atalho para a distribuicao do tipo `Busca de depoimentos`

## 5.2 Participantes com cartas para impressao

Mostra a quantidade de participantes com pelo menos uma carta elegivel para impressao principal.

O cartao deve apresentar:

- quantidade de participantes elegiveis
- quantidade total de cartas elegiveis, como informacao secundaria
- atalho para a distribuicao do tipo `Impressao principal`

A contagem deve ignorar cartas:

- arquivadas ou sem aprovacao administrativa
- ja decididas no Fluxo de Impressao
- vinculadas a um fluxo aberto

## 5.3 Participantes com cartas para revisao

Mostra a quantidade de participantes com pelo menos uma carta aguardando a primeira reavaliacao.

O cartao deve apresentar:

- quantidade de participantes elegiveis
- quantidade total de cartas aguardando reavaliacao, como informacao secundaria
- atalho para a distribuicao do tipo `Reavaliacao de cartas`

Uma carta deixa de entrar nessa contagem quando:

- for aprovada
- estiver em outro fluxo aberto
- ja tiver uma reavaliacao concluida, mesmo que continue reprovada

Cartas ja reavaliadas e ainda reprovadas permanecem acessiveis pela fila manual, mas nao aumentam o numero deste cartao.

## 5.4 Comportamento visual dos cartoes

- manter a estetica dos cartoes administrativos existentes
- utilizar icones diferentes para meta, impressao e revisao
- exibir tooltip Bootstrap explicando exatamente a regra de cada numero
- permitir abrir a distribuicao ja com o tipo correspondente selecionado
- manter tres colunas equilibradas no desktop e empilhar no celular
- mostrar zero sem ocultar o cartao, para que a equipe entenda que nao ha pendencias

---

## 6. Historico e contagem de revisoes

Cada decisao deve continuar sendo um registro separado e imutavel. Nenhuma nova revisao pode sobrescrever a anterior.

O historico deve usar os dados ja relacionados por:

- evento
- fluxo
- carta
- membro da equipe
- decisao
- motivo da reprovacao
- data e horario

### 6.1 Contadores por carta

O sistema deve calcular:

- `total de revisoes`: todas as decisoes registradas para a carta
- `revisoes iniciais`: decisoes realizadas em fluxos de impressao principal
- `total de reavaliacoes`: decisoes realizadas em fluxos de reavaliacao
- `ultima decisao`: decisao mais recente pela data e pelo identificador do registro

Esses valores devem ser derivados do historico, sem um contador manual sujeito a divergencia.

### 6.2 Identificacao dos revisores

Para cada revisao, a interface administrativa deve mostrar:

- nome do membro que realizou a revisao
- tipo do fluxo
- decisao tomada
- motivo, quando reprovada
- data e horario

O cadastro do membro nao deve ser excluido fisicamente quando possuir revisoes. A inativacao deve preservar seu nome no historico.

### 6.3 Marcacoes visuais

As cartas devem usar marcacoes claras:

- `Nao revisada`
- `Reprovada - aguardando reavaliacao`
- `Em tarefa de reavaliacao`
- `Ja reavaliada N vez(es)`
- `Aprovada`

Quando a decisao mais recente continuar reprovada, deve aparecer tambem o botao `Ver historico` e, na fila manual, `Reavaliar novamente`.

---

## 7. Filtro de membros responsaveis

O select deve mostrar somente membros que:

- estejam ativos
- estejam autorizados no evento atual
- ainda nao tenham atingido seu limite efetivo de tarefas abertas

O limite efetivo sera:

1. limite individual do membro, quando configurado
2. limite global do sistema, quando nao houver limite individual

A carga deve considerar tarefas abertas de todos os eventos.

O rotulo de cada opcao deve seguir o formato:

```text
Nome do membro - 2/4 tarefas - 2 vagas
```

O backend deve repetir a validacao no momento da gravacao. Um membro que tenha atingido o limite entre o carregamento da tela e o envio do formulario deve ser rejeitado com uma mensagem clara.

---

## 8. Pagina de compartilhamento do fluxo

Depois de `Distribuir fluxo`, o sistema nao deve voltar diretamente para a listagem geral. Deve redirecionar para uma pagina exclusiva de compartilhamento.

A pagina deve mostrar:

- evento
- tipo da tarefa
- participante
- membro responsavel
- quantidade de cartas selecionadas, quando aplicavel
- validade do link
- limite de acessos
- link temporario

### Acoes

- `Copiar link`
- `Abrir WhatsApp Web`
- `Voltar para os fluxos`
- `Gerar novo link`

Mensagem sugerida:

```text
Ola, {membro}! Voce recebeu uma tarefa de {tipo} para {participante} no evento {evento}. Acesse o link temporario: {url}
```

O WhatsApp deve abrir em nova aba com a mensagem preenchida. O envio continua manual.

### Seguranca do link

- o token continua armazenado somente como hash
- o valor original aparece apenas imediatamente depois da criacao ou renovacao
- atualizar ou revisitar a pagina nao pode revelar o token novamente
- quando o valor nao estiver mais disponivel, a pagina deve oferecer `Gerar novo link`
- a renovacao invalida o token anterior

---

## 9. Servico de candidatos e validacao transacional

A implementacao deve centralizar as regras de elegibilidade em um servico unico, reutilizado pelos cartoes, pela tela de distribuicao e pela validacao do envio.

Nome sugerido:

```text
PrintFlowCandidateService
```

Endpoint sugerido:

```text
GET /admin/print-flows/distribution-options?type={type}
```

O retorno deve conter somente dados do evento atual:

- participantes elegiveis
- cartas elegiveis
- contagens operacionais
- membros disponiveis

O envio da distribuicao deve aceitar `testimonial_ids[]` para impressao principal e reavaliacao. A busca de depoimentos deve rejeitar qualquer identificador de carta.

Antes da gravacao, uma transacao deve:

1. bloquear o participante e as cartas selecionadas para atualizacao
2. recalcular a elegibilidade
3. validar evento, participante, tipo e membro
4. impedir que duas distribuicoes concorrentes usem a mesma carta
5. criar o fluxo e vincular apenas as cartas selecionadas

---

## 10. Filtros adicionais de gestao

A listagem administrativa deve manter os filtros atuais e acrescentar:

- somente fluxos com cartas
- somente fluxos aguardando revisao
- somente fluxos prontos para impressao
- somente cartas nunca revisadas
- cartas reprovadas aguardando primeira reavaliacao
- cartas reavaliadas e ainda reprovadas
- membro que realizou a ultima revisao
- quantidade minima de reavaliacoes

Os filtros devem preservar o evento atual e funcionar em conjunto com participante, membro, tipo, status e vencimento.

---

## 11. Testes automatizados

### Candidatos e cartoes

- impressao principal lista somente cartas aprovadas administrativamente, nunca decididas e sem fluxo aberto
- cartao de impressao conta participantes distintos e informa o total de cartas
- reavaliacao automatica lista somente cartas reprovadas que nunca passaram por reavaliacao
- cartao de revisao exclui cartas ja reavaliadas
- carta ja reavaliada e ainda reprovada aparece na fila manual
- busca lista somente participantes abaixo da meta e sem tarefa igual aberta
- todos os calculos respeitam o evento atual

### Historico

- cada nova decisao cria um registro e nao altera o anterior
- total de revisoes e total de reavaliacoes sao calculados corretamente
- ultimo revisor, ultima decisao e data correspondem ao registro mais recente
- revisores inativos continuam identificados no historico
- carta aprovada deixa de ser elegivel para nova reavaliacao

### Distribuicao

- mudar o tipo limpa selecoes incompativeis
- somente as cartas marcadas sao vinculadas ao fluxo
- identificadores adulterados de outro participante, evento ou tipo sao rejeitados
- membro no limite nao aparece nas opcoes e tambem e rejeitado pelo backend
- distribuicoes concorrentes nao duplicam a mesma carta

### Compartilhamento

- distribuicao redireciona para a pagina exclusiva
- URL e mensagem do WhatsApp correspondem ao evento e ao membro
- recarregar a pagina nao revela o token original
- renovacao invalida o token antigo e disponibiliza o novo uma unica vez

---

## 12. Testes visuais no navegador

Validar nos dominios locais do Vida Vitoriosa e do EDD:

- os tres cartoes no desktop e no celular
- tooltips e atalhos dos cartoes
- troca entre os tres tipos de tarefa
- pesquisa de participante
- lista de cartas com todas selecionadas inicialmente
- marcacoes de carta nao revisada, reprovada, em reavaliacao, reavaliada e aprovada
- contador e historico de revisores
- filtro de membros e estado sem membros disponiveis
- pagina de compartilhamento
- botao de copiar
- abertura do WhatsApp Web com texto correto
- estados vazios, carregamento e erro

Os testes devem confirmar ausencia de mistura entre os eventos e ausencia de erros relevantes no console e na rede.

---

## 13. Criterios de aceite

- a tela de distribuicao apresenta o tipo acima e as relacoes abaixo
- cada tipo mostra somente candidatos elegiveis
- o administrador escolhe individualmente as cartas do fluxo
- membros no limite nao podem ser selecionados
- os tres cartoes exibem contagens coerentes com as mesmas regras da distribuicao
- cartas ja reavaliadas ficam identificadas e fora da contagem automatica
- cartas ainda reprovadas podem ser enviadas manualmente para nova reavaliacao
- o sistema informa quantas revisoes ocorreram e quem realizou cada uma
- o historico permanece imutavel
- a distribuicao abre a pagina exclusiva de compartilhamento
- o token continua protegido e visivel apenas uma vez
- testes automatizados e visuais sao aprovados nos dois eventos

---

## 14. Decisoes congeladas

- `Nao revisada` significa que a carta nao possui nenhuma decisao no Fluxo de Impressao.
- A fila automatica de reavaliacao inclui somente cartas reprovadas que ainda nao passaram por uma reavaliacao concluida.
- Uma carta reavaliada e ainda reprovada sai do cartao automatico, mas pode receber nova tarefa pela fila manual.
- O contador do cartao representa participantes distintos; a quantidade de cartas aparece como informacao secundaria.
- O historico existente e a fonte dos contadores e dos nomes dos revisores.
- O envio pelo WhatsApp permanece manual.
- O limite de tarefas do membro continua global entre eventos.
- O token original nao sera persistido de forma recuperavel.

---

## 15. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao do planejamento evolutivo da distribuicao, cartoes operacionais, fila automatica e manual de reavaliacao, contadores e historico de revisores | Consolidar as melhorias solicitadas sem confundir o comportamento futuro com o modulo ja implementado |
