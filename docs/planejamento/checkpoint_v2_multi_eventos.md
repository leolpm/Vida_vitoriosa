# Checkpoint de implementacao - v2.0.0 Multi-eventos

## Estado do checkpoint

- Data: 2026-08-18
- Branch: `codex/v2-multi-eventos`
- Base preservada: tag `v1.0.0`, commit `5e80989`
- Estado: implementacao interrompida de forma intencional e ainda nao pronta para producao

## Concluido neste checkpoint

- versao estavel anterior publicada em `main` e na tag `v1.0.0`
- planejamento de versoes criado
- modelos `Event`, `EventDomain` e `EventSetting` criados
- tabela de permissoes `event_user` planejada e criada pela migration
- migration multi-evento criada e executada com sucesso no banco local
- dados existentes associados ao evento Vida Vitoriosa
- dominios de producao e locais cadastrados
- aliases `localhost` e `127.0.0.1` associados ao Vida Vitoriosa no ambiente local
- contexto `CurrentEvent` e middleware de resolucao por dominio criados
- pagina de falha fechada para dominio desconhecido criada
- escopo automatico por evento adicionado a participantes, depoimentos e lotes PDF
- configuracoes visuais e textuais encaminhadas para `event_settings`
- login administrativo passou a validar permissao no evento atual
- novos usuarios administrativos recebem acesso aos eventos ativos
- uploads de depoimentos e configuracoes passaram a usar diretorios por evento
- reset passou a considerar somente o evento atual
- nomes de arquivos Excel passaram a incluir o slug do evento
- cabecalhos de relatorios passaram a identificar o evento
- navegacao administrativa passou a identificar e permitir troca de evento
- textos dinamicos e identidade visual azul do formulario EDD implementados inicialmente
- artes fornecidas adicionadas em `public/images/events/edd/`

## Estado do banco local

A migration `2026_08_18_000000_create_multi_event_structure` foi aplicada com sucesso.

Contagens verificadas logo apos a migration:

| Entidade | Evento | Total |
|---|---|---:|
| Participantes existentes | Vida Vitoriosa | 8 |
| Depoimentos existentes | Vida Vitoriosa | 14 |
| Lotes PDF existentes | Vida Vitoriosa | 21 |

Nenhum dado de demonstracao do EDD foi inserido no banco local neste checkpoint, porque o `DatabaseSeeder` nao foi executado depois da migration. O seeder ja contem a estrutura inicial desses dados para uma validacao posterior.

## Pendente antes de concluir a v2.0.0

1. revisar o `DatabaseSeeder` completo e executar em banco descartavel
2. adaptar a infraestrutura dos testes para definir dominio e evento padrao
3. criar testes de isolamento entre Vida Vitoriosa e EDD
4. testar formulario, POST, login, dashboard, participantes, depoimentos, relatorios, PDF e reset nos dois dominios
5. revisar integridade cruzada entre participante, depoimento e lote PDF
6. revisar os previews e fallbacks de imagem nas configuracoes
7. validar a arte EDD no desktop e no celular em navegador real
8. revisar o PDF do EDD e o caminho de armazenamento por evento
9. executar a suite completa de testes
10. atualizar a documentacao canonica apos a validacao
11. criar o commit final da v2.0.0 somente depois de todos os criterios de aceite

## Comandos para retomar

```powershell
cd C:\Developer\Igreja_vida
git switch codex/v2-multi-eventos
git status
php artisan migrate:status
php artisan serve
```

Dominios locais esperados na porta 8888:

```text
http://vidavitoriosa.atitudelaranja.test:8888/
http://edd.atitudelaranja.test:8888/
```

## Regra de retomada

Nao iniciar a implementacao do novo Fluxo de Impressao antes de concluir os testes automatizados e visuais desta fundacao multi-evento.

## Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao do checkpoint parcial da v2.0.0 | Permitir desligamento e retomada segura sem perder o estado tecnico da implementacao |
