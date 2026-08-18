# Roadmap do Fluxo de Impressao

> Status: ativo
> Tipo: roadmap de implementacao
> Fonte canonica relacionada: `docs/planejamento/planejamento_fluxo_impressao.md`
> Dependencia arquitetural: `docs/planejamento/planejamento_multi_eventos.md`
> Dependencia visual: `docs/ui-standards.md`

## 1. Objetivo

Organizar a implementacao do Fluxo de Impressao em etapas executaveis, com dependencias claras, criterios de conclusao e pontos de controle.

Este roadmap nao substitui o planejamento funcional. Ele apenas traduz o planejamento em ordem de execucao.

---

## 2. Principios de sequenciamento

- primeiro concluir e validar a arquitetura multi-evento
- em seguida estruturar a base de dados e as configuracoes
- depois liberar a distribuicao e o acesso controlado por link
- em seguida implementar revisao, aprovacao e impressao
- depois cobrir reavaliacao de cartas e participantes criticos
- por fim ajustar dados de teste, auditoria e refinamentos visuais
- executar testes automatizados e testes no navegador durante cada fase aplicavel

---

## 3. Roadmap por fase

## Fase 0 - Arquitetura multi-evento

### Objetivo

Implementar e validar integralmente `docs/planejamento/planejamento_multi_eventos.md` antes de criar o Fluxo de Impressao.

### Entregaveis

- eventos Vida Vitoriosa e EDD
- resolucao do evento pelo dominio
- dados atuais migrados para Vida Vitoriosa
- configuracoes e arquivos separados por evento
- formularios publicos dos dois eventos
- administracao isolada por evento
- dominios locais `.test` funcionando
- testes automatizados de isolamento
- validacao visual dos dois eventos no navegador

### Dependencias

- `docs/planejamento/planejamento_multi_eventos.md` aprovado
- backup e inventario dos dados atuais
- hosts locais configurados para os testes no navegador

### Criterio de saida

- os criterios de aceite do planejamento multi-evento foram atendidos e o Fluxo de Impressao pode reutilizar o contexto de evento sem criar uma segunda arquitetura

---

## Fase 1 - Base operacional

### Objetivo

Criar a estrutura minima para administrar equipe, configuracoes e distribuicao de fluxos.

### Entregaveis

- menu atualizado com Fluxo de Impressao e Equipe
- cadastro de equipe com nome e telefone
- configuracoes do sistema para limites e validade
- tela de distribuicao com listagem dos fluxos ja enviados
- filtros basicos para gestao dos fluxos distribuidos

### Dependencias

- modelo de dados da equipe
- modelo de dados de distribuicao de fluxo
- configuracoes persistentes

### Criterio de saida

- e possivel cadastrar equipe, configurar parametros e distribuir um fluxo para um membro da equipe

---

## Fase 2 - Acesso controlado por link

### Objetivo

Permitir que o membro da equipe acesse somente o fluxo autorizado, com limite de uso e validade.

### Entregaveis

- link com acesso unico ou limite configuravel
- validade padrao de 30 minutos configuravel
- bloqueio de acesso expirado ou excedido
- mensagem pronta para envio via WhatsApp Web
- rastreio do uso do link na distribuicao

### Dependencias

- fase 1 concluida
- mecanismo de token ou assinatura para o link
- validacao de expiracao e contagem de acessos

### Criterio de saida

- o membro consegue abrir apenas o fluxo permitido e o sistema bloqueia links invalidos

---

## Fase 3 - Revisao das cartas

### Objetivo

Liberar a avaliacao das cartas do participante antes da impressao.

### Entregaveis

- etapa visual no topo com progresso do fluxo
- tela de revisao das cartas com imagens quando existirem
- aprovacao e reprovacao por carta
- registro do motivo da reprovacao quando necessario
- manutencao de historico da decisao

### Dependencias

- fase 2 concluida
- definicao da experiencia de revisao no mesmo padrao visual do sistema

### Criterio de saida

- o membro consegue revisar as cartas e separar o que segue para impressao do que precisa de reavaliacao

---

## Fase 4 - Impressao e conclusao

### Objetivo

Entregar a operacao de impressao do lote e a confirmacao final do fluxo.

### Entregaveis

- tela de impressao com nome do autor de cada depoimento
- quantidade de paginas por depoimento
- total de paginas do lote
- botao de imprimir que abre a janela do navegador
- confirmacao de conclusao da impressao
- liberacao de novo fluxo para o mesmo membro quando o lote for concluido

### Dependencias

- fase 3 concluida
- regra de calculo de paginas validada

### Criterio de saida

- o lote pode ser impresso e encerrado dentro do fluxo controlado

---

## Fase 5 - Reavaliacao e participantes criticos

### Objetivo

Adicionar a camada de operacao secundaria para cartas reprovadas e para participantes abaixo da meta minima.

### Entregaveis

- fluxo de revisao das cartas reprovadas
- redistribuicao para outro membro da equipe
- filtro de participantes criticos
- mecanismo para criar tarefas de busca de depoimentos para participantes criticos
- limite de tarefas por membro considerando abertas, em andamento e vencidas

### Dependencias

- fases 3 e 4 concluidas
- configuracao da quantidade minima de depoimentos
- fila de tarefas por membro da equipe

### Criterio de saida

- o sistema organiza reavaliacao, prioridade operacional e limite de carga por membro

---

## Fase 6 - Dados de teste e refinamentos

### Objetivo

Revisar a base de testes para validar o fluxo novo de ponta a ponta e preparar ajustes finos.

### Entregaveis

- dados de teste refeitos
- participantes de teste alinhados ao novo fluxo
- cartas com e sem imagem para validar layout adaptativo
- fluxos distribuidos de teste para cobertura dos estados principais
- tarefas em aberto, vencidas e concluidas para validar limites
- ajustes finais de auditoria e rastreio

### Dependencias

- fases anteriores implementadas ou simuladas
- acesso ao ambiente de testes

### Criterio de saida

- a equipe consegue validar o fluxo novo com dados coerentes e cenarios representativos

---

## 4. Marcas de conclusao por fase

Cada fase so deve ser considerada concluida quando houver:

- tela ou comportamento esperado implementado
- validacoes principais funcionando
- sem dependencia bloqueando a fase seguinte
- dados de teste suficientes para verificacao
- testes automatizados relevantes aprovados
- validacao visual no navegador em desktop e celular quando houver interface
- ausencia de erros relevantes no console e na rede do navegador

---

## 5. Riscos e controles

### Risco: dependencias de WhatsApp sem integracao oficial

Controle:

- manter o envio manual via WhatsApp Web
- gerar apenas a mensagem e o link pronto

### Risco: duplicidade de decisao entre documentos

Controle:

- considerar o planejamento funcional como fonte canonica
- tratar este arquivo como ordem de execucao, nao como substituto

### Risco: base antiga de testes mascarar problemas do novo fluxo

Controle:

- refazer os dados de teste antes da validacao final

### Risco: fluxo ignorar o evento atual

Controle:

- concluir o planejamento multi-evento antes da fase 1
- exigir `event_id` em todas as entidades futuras do fluxo
- testar links, revisoes e impressoes nos dois dominios

---

## 6. Pendencias abertas para execucao futura

- definir o modelo exato do token de acesso do link
- definir os campos finais do cadastro de equipe
- definir o formato da fila de tarefas por membro
- definir a persistencia do historico de revisao e aprovacao
- confirmar o reaproveitamento total do design atual nas novas telas

---

## 7. Resultado esperado do roadmap

Ao final do roadmap, o sistema deve operar com:

- distribuicao controlada de fluxos
- acesso restrito por link
- revisao das cartas antes da impressao
- impressao por lote com confirmacao
- reavaliacao das cartas reprovadas
- controle de participantes criticos
- limite de tarefas por membro
- dados de teste coerentes com a nova implementacao
- operacao isolada entre Vida Vitoriosa e EDD
- validacao automatizada e visual nos dois dominios

---

## 8. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Fase 0 substituida pela arquitetura multi-evento e inclusao de testes automatizados e visuais como criterio de conclusao | Alinhar o roadmap ao planejamento por subdominios antes da implementacao do Fluxo de Impressao |
