# Documentacao de Modulos

Este diretorio concentra a documentacao canonica dos modulos do sistema.

## Regras

- usar um arquivo por modulo
- evitar duplicar decisoes que ja estejam em outro documento canonico
- quando um documento for substituido, marcar isso no topo do arquivo substituto
- se o documento antigo ainda tiver valor de consulta, mover para `docs/historico/`
- quando houver padrao visual compartilhado, consultar `docs/ui-standards.md` antes de criar ou alterar UI

## Template canonico por modulo

Use `docs/modulos/template-modulo.md` como base para novos modulos ou revisoes completas.

## Modulos documentados

- `docs/modulos/multi-eventos.md`: resolucao por dominio, isolamento de dados, EDD, operacao e testes da v2.0.0

## Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao do indice de documentacao modular | Preparar fontes canonicas para as proximas versoes do sistema |
| 2026-08-18 | Inclusao do modulo multi-eventos | Consolidar a especificacao da arquitetura implementada na v2.0.0 |
