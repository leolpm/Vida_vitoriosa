# Padrao de Planejamento

Use este diretorio para roadmap, backlog e planejamento funcional.

## Regras canonicas

- manter apenas uma fonte ativa para cada decisao
- se um planejamento substituir outro, deixar isso explicitado no topo do documento novo
- se o documento antigo ainda for util, mover para `docs/historico/`
- nao repetir no planejamento decisoes que ja estejam consolidadas na documentacao canonica do modulo
- quando o planejamento tocar em visual compartilhado, consultar `docs/ui-standards.md`

## Sugestao de cabecalho para novos documentos

```md
# Titulo do Planejamento

> Status: ativo | substituido | historico
> Fonte canonica relacionada: `docs/modulos/<modulo>.md`
> Dependencia visual: `docs/ui-standards.md`
```

## Planejamentos ativos

- `planejamento_multi_eventos.md`: fonte das decisoes de eventos, dominios, migracao, isolamento e testes locais
- `planejamento_fluxo_impressao.md`: fonte funcional e arquitetural do Fluxo de Impressao
- `planejamento_melhoria_distribuicao_fluxos.md`: evolucao da distribuicao, indicadores operacionais e rastreio de reavaliacoes
- `roadmap_fluxo_impressao.md`: sequenciamento da implementacao do Fluxo de Impressao
- `planejamento_versoes.md`: politica de tags, branches e ordem das versoes
- `checkpoint_v2_multi_eventos.md`: estado exato da implementacao parcial da v2.0.0 e instrucoes de retomada

## Ordem entre os planejamentos

1. implementar e validar `planejamento_multi_eventos.md`
2. implementar `planejamento_fluxo_impressao.md`
3. usar `roadmap_fluxo_impressao.md` como sequenciamento da segunda etapa

## Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Inclusao do planejamento multi-evento e da ordem de execucao entre os documentos | Evitar inicio do Fluxo de Impressao antes da fundacao por eventos e dominios |
| 2026-08-18 | Inclusao da governanca de versoes | Congelar a v1.0.0 e separar as implementacoes v2.0.0 e v3.0.0 |
| 2026-08-18 | Inclusao do checkpoint da implementacao v2.0.0 | Registrar o ponto seguro de interrupcao e as pendencias para retomada |
| 2026-08-18 | Inclusao do planejamento evolutivo da distribuicao de fluxos | Registrar candidatos dinamicos, novos cartoes e historico completo de reavaliacoes antes da implementacao |
