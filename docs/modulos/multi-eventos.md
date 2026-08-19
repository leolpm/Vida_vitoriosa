# Modulo Multi-eventos

## 1. Visao geral

O modulo permite operar Vida Vitoriosa e EDD na mesma aplicacao Laravel, com identidade, configuracoes, usuarios autorizados e dados isolados pelo dominio acessado.

Eventos ativos:

| Slug | Producao | Ambiente local |
|---|---|---|
| `vida-vitoriosa` | `vidavitoriosa.atitudelaranja.com` | `vidavitoriosa.atitudelaranja.test:8888` |
| `edd` | `edd.atitudelaranja.com` | `edd.atitudelaranja.test:8888` |

O dominio e a unica fonte confiavel para definir o evento da requisicao. Campos enviados pelo navegador nao podem trocar o evento atual.

## 2. Fluxos

1. `ResolveCurrentEvent` normaliza o host e procura um `EventDomain` ativo no ambiente configurado.
2. O evento encontrado e armazenado no servico de escopo `CurrentEvent`.
3. Participantes, depoimentos, lotes PDF, configuracoes e arquivos passam a usar esse contexto.
4. Um dominio desconhecido encerra a requisicao com HTTP 404 antes de consultar dados operacionais.
5. No painel, um administrador autorizado pode alternar para outro evento pelo seletor superior.
6. A sessao e compartilhada entre os subdominios de cada ambiente.

## 3. Regras de negocio

- `participants`, `testimonials` e `pdf_batches` exigem `event_id` no banco.
- O trait `BelongsToEvent` aplica o escopo global e preenche `event_id` ao salvar.
- Criar um registro operacional sem `CurrentEvent` gera `LogicException`.
- Um depoimento deve pertencer ao mesmo evento de seu participante e lote PDF.
- Um lote PDF deve pertencer ao mesmo evento de seu participante.
- Configuracoes visuais e textuais sao salvas em `event_settings`.
- A expiracao do codigo de login permanece global em `settings`.
- Uploads usam `storage/app/public/events/{slug}/...`.
- PDFs usam `storage/app/public/events/{slug}/pdf/participant-{id}/...`.
- O reset apaga apenas dados e arquivos do evento atual e exige a frase `RESETAR {NOME DO EVENTO}`.
- Relatorios Excel incluem o slug do evento no nome do arquivo.
- O encerramento de depoimentos e independente para cada evento.

## 4. Permissoes

- A tabela `event_user` define em quais eventos cada usuario administrativo pode entrar.
- O login por codigo valida a permissao no evento do dominio em que foi iniciado.
- O middleware administrativo bloqueia usuarios sem acesso ao evento atual.
- A troca de evento mostra somente eventos ativos autorizados ao usuario.
- Novos administradores recebem acesso aos eventos ativos conforme a regra atual do cadastro administrativo.
- Por compatibilidade com cadastros legados, um administrador sem nenhum vinculo em `event_user` possui acesso geral; assim que existir um vinculo, somente os eventos ativos vinculados ficam acessiveis.

## 5. Modelo de dados

| Entidade | Responsabilidade |
|---|---|
| `events` | Cadastro e identidade basica do evento |
| `event_domains` | Hosts de producao e local vinculados ao evento |
| `event_settings` | Textos, datas e caminhos de imagens por evento |
| `event_user` | Permissao de usuario por evento |
| `participants.event_id` | Isolamento dos participantes |
| `testimonials.event_id` | Isolamento dos depoimentos |
| `pdf_batches.event_id` | Isolamento dos lotes PDF |

As chaves de evento dos dados operacionais sao obrigatorias. As migrations existentes associam os dados anteriores ao Vida Vitoriosa antes de tornar as colunas nao nulas.

## 6. Rotas e dominios

As rotas publicas e administrativas continuam com os mesmos caminhos. O significado de cada rota depende do host.

Exemplos:

```text
http://vidavitoriosa.atitudelaranja.test:8888/
http://edd.atitudelaranja.test:8888/
http://edd.atitudelaranja.test:8888/admin/dashboard
```

Configuracao local necessaria no arquivo `hosts`:

```text
127.0.0.1 vidavitoriosa.atitudelaranja.test
127.0.0.1 edd.atitudelaranja.test
```

O ambiente do dominio e definido por `EVENT_DOMAIN_ENVIRONMENT`. A aplicacao usa a porta atual ao gerar links locais.

## 7. UI

O painel administrativo mantem o mesmo design nos eventos e exibe o nome do contexto no menu lateral e no seletor superior.

O formulario Vida Vitoriosa preserva sua identidade existente. O formulario EDD usa:

- arte oficial azul em banner panoramico
- titulo e texto especificos para lider ou supervisor
- termo `Liderado` para o destinatario
- aviso de surpresa especifico
- relacoes `Lider`, `Supervisor`, `Pastor`, `Coordenador` e `Outro`

No celular, o cabecalho publico e centralizado, o banner nao gera rolagem horizontal e o menu administrativo fica recolhido em um painel acionado pelo botao `Menu`.

As regras visuais compartilhadas estao em `docs/ui-standards.md`.

## 8. Erros e excecoes

- dominio nao cadastrado ou inativo: HTTP 404
- usuario sem permissao no evento: acesso administrativo negado
- participante de outro evento no POST publico: erro de validacao
- relacao cruzada entre eventos no modelo: `LogicException`
- registro operacional sem evento: `LogicException`
- `event_id` nulo por acesso direto ao banco: violacao de integridade
- GD ausente ao gerar PDF: mensagem administrativa orientando habilitar a extensao

## 9. Operacao

Preparacao e execucao local:

```powershell
php artisan migrate
php artisan db:seed --class=EddDemoSeeder
php artisan serve
```

`php artisan serve` usa a porta 8888 pela configuracao do projeto.

O `EddDemoSeeder` e idempotente e cria tres participantes, um depoimento aprovado e acesso ao EDD para os administradores existentes. O `DatabaseSeeder` completo recria os dois eventos e seus dados de demonstracao.

## 10. Testes

Cobertura automatizada principal:

- identidade e participantes isolados no formulario publico
- POST determinado pelo host e rejeicao de participante estrangeiro
- upload no diretorio do evento
- datas de encerramento independentes
- permissao administrativa e model binding com falha fechada
- dominio desconhecido sem vazamento de dados
- exigencia de contexto para modelos operacionais
- integridade entre participante, depoimento e lote
- restricao de `event_id` nao nulo no banco
- reset preservando outro evento
- relatorios com nome de arquivo por evento
- execucao completa do `DatabaseSeeder`

Validacao visual executada em navegador real:

- formularios Vida Vitoriosa e EDD em desktop e celular
- busca de participante dentro do select publico
- login por codigo e sessao compartilhada entre subdominios
- dashboards e metricas isoladas
- menu administrativo movel
- configuracoes e previews EDD
- participantes, depoimentos, relatorios e PDF EDD
- geracao e download real de PDF EDD com uma pagina

Padrao compartilhado de requisicoes:

- formularios publicos e administrativos exibem carregamento contextual no botao acionado
- o envio do codigo administrativo permite uma solicitacao a cada 60 segundos por evento, usuario e IP
- tentativas repetidas preservam o codigo ja enviado e informam o tempo restante
- as regras visuais canonicas estao em `docs/ui-standards.md`

## 11. Pendencias

- publicar a branch validada e criar a tag `v2.0.0` somente no ponto de release aprovado
- configurar os dominios reais, HTTPS e `SESSION_DOMAIN=.atitudelaranja.com` na implantacao
- implementar o Fluxo de Impressao da v3 sobre este contexto, sem criar uma segunda estrategia de eventos

## 12. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao da documentacao canonica do modulo multi-eventos | Registrar a arquitetura implementada, operacao, isolamento e validacoes da v2.0.0 |
| 2026-08-19 | Inclusao do feedback global e limite de reenvio do login | Padronizar a espera do servidor sem misturar o contexto dos eventos |
