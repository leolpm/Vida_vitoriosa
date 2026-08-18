# Planejamentos e Roadmap

Este diretorio concentra planejamento, backlog, roadmap e documentos de decisao temporal.

## Regras

- nao misturar planejamento com especificacao canonica de modulo
- evitar duplicidade de decisao entre arquivos
- cada planejamento deve deixar claro se e ativo, substituido ou historico
- quando um documento for substituido, marcar isso no topo do arquivo novo
- se o documento antigo continuar util, mover para `docs/historico/`
- documentos de planejamento devem referenciar a documentacao canonica do modulo quando ela existir
- quando houver padrao visual compartilhado, consultar `docs/ui-standards.md` antes de detalhar interface

## Estrutura esperada

- `docs/planejamento/` para roadmap, backlog e planejamento evolutivo
- `docs/historico/` para documentos substituidos, mas ainda uteis
- `docs/modulos/` para documentacao canonica de modulos

## Documentos ativos

### Planejamento

- `docs/planejamento/planejamento_multi_eventos.md`: arquitetura multi-evento, dominios, migracao e estrategia de testes
- `docs/planejamento/planejamento_fluxo_impressao.md`: planejamento funcional do Fluxo de Impressao
- `docs/planejamento/roadmap_fluxo_impressao.md`: ordem de execucao do Fluxo de Impressao
- `docs/planejamento/planejamento_versoes.md`: tags, branches e ordem das versoes estaveis

### Padroes

- `docs/ui-standards.md`: referencia visual compartilhada
- `docs/modulos/template-modulo.md`: estrutura canonica para documentacao de modulos

## Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Inclusao do indice de planejamentos ativos e referencias compartilhadas | Facilitar a navegacao e registrar o planejamento multi-evento como fonte ativa |
| 2026-08-18 | Inclusao do planejamento de versoes | Preservar a v1.0.0 antes da implementacao multi-evento |
