# Planejamento de Versoes

> Status: ativo
> Tipo: governanca de versoes e entregas
> Dependencia visual: `docs/ui-standards.md`

## 1. Objetivo

Preservar versoes estaveis do sistema antes de alteracoes estruturais e manter uma ordem clara entre a arquitetura multi-evento e o futuro Fluxo de Impressao.

O projeto usara tags Git para marcar versoes estaveis e branches separadas para evolucoes de grande porte.

---

## 2. Convencao

O projeto seguira versionamento semantico:

```text
MAJOR.MINOR.PATCH
```

- MAJOR: mudanca estrutural ou incompatibilidade relevante
- MINOR: nova funcionalidade compativel
- PATCH: correcao sem mudanca estrutural

Exemplos:

```text
v1.0.0
v1.1.0
v1.1.1
v2.0.0
```

---

## 3. Linha de versoes

| Versao | Estado | Escopo |
|---|---|---|
| `v1.0.0` | Estavel e congelada | Sistema atual do Vida Vitoriosa em modo monoevento |
| `v2.0.0` | Implantada; EDD aguarda DNS e SSL | Arquitetura multi-evento, subdominios e formulario EDD |
| `v3.0.0` | Planejada | Fluxo de Impressao multi-evento |

## 3.1 Versao 1.0.0 - Vida Vitoriosa

Representa o estado funcional anterior a arquitetura multi-evento.

Inclui:

- formulario publico do Vida Vitoriosa
- login administrativo por codigo enviado por e-mail
- usuarios administrativos
- participantes e importacao em massa
- depoimentos, telefone e imagens
- status e filtros administrativos
- geracao e historico de PDFs
- relatorios e exportacao Excel
- configuracoes visuais
- encerramento dos depoimentos por data e horario
- reset do sistema com confirmacao
- ajustes responsivos no site publico e painel

Tag Git:

```text
v1.0.0
```

Essa tag deve permitir restaurar a versao atual sem depender do codigo multi-evento.

## 3.2 Versao 2.0.0 - Multi-eventos e EDD

Fonte de planejamento:

```text
docs/planejamento/planejamento_multi_eventos.md
```

Branch de implementacao:

```text
codex/v2-multi-eventos
```

Escopo:

- eventos resolvidos pelo dominio
- Vida Vitoriosa e EDD na mesma aplicacao
- dominios locais `.test`
- isolamento de participantes, depoimentos, configuracoes e arquivos
- formulario EDD com identidade azul propria
- arte EDD fornecida pelo usuario incorporada ao projeto
- sessao compartilhada entre subdominios
- reset limitado ao evento atual
- classificacao dos 61 participantes existentes como dados do EDD
- testes automatizados de isolamento
- testes visuais nos dois dominios

Tag publicada depois da validacao:

```text
v2.0.0
```

## 3.3 Versao 3.0.0 - Fluxo de Impressao

Fontes de planejamento:

```text
docs/planejamento/planejamento_fluxo_impressao.md
docs/planejamento/roadmap_fluxo_impressao.md
```

Essa versao somente deve comecar depois da publicacao e validacao da `v2.0.0`.

Escopo principal:

- equipe operacional
- distribuicao de fluxos
- links com validade e limite de acesso
- revisao das cartas
- impressao pelo navegador
- confirmacao da conclusao
- reavaliacao de cartas
- participantes criticos
- auditoria do fluxo

Tag prevista:

```text
v3.0.0
```

---

## 4. Processo antes de uma versao estrutural

1. concluir funcionalidades e documentacao da versao atual
2. verificar arquivos pendentes no Git
3. excluir artefatos temporarios do versionamento
4. executar validacoes disponiveis
5. criar commit descritivo de congelamento
6. criar tag da versao estavel
7. enviar commit e tag ao repositorio remoto
8. criar branch para a nova versao
9. iniciar migrations e alteracoes estruturais somente na nova branch

---

## 5. Regras de commit

- cada commit deve representar uma alteracao coerente
- migrations e modelos relacionados devem permanecer no mesmo conjunto funcional
- nao misturar arquivos temporarios ou PDFs de teste
- atualizar documentacao quando comportamento ou arquitetura mudar
- executar testes antes de marcar uma versao
- nao alterar uma tag publicada

Exemplos de mensagens:

```text
Release v1.0.0 baseline
Add multi-event domain resolution
Scope participant data by event
Add EDD public testimonial form
Complete multi-event visual validation
```

---

## 6. Criterios para criar uma tag

Uma tag estavel somente pode ser criada quando:

- o escopo previsto estiver concluido
- migrations tiverem sido validadas
- testes automatizados disponiveis passarem
- testes visuais obrigatorios tiverem sido executados
- nao houver erro funcional conhecido de alta severidade
- documentacao estiver atualizada
- arquivos temporarios estiverem fora do Git

---

## 7. Recuperacao de versao

As tags servem como referencia imutavel para consulta, comparacao e recuperacao.

Para inspecionar a versao atual congelada:

```powershell
git switch --detach v1.0.0
```

Para voltar ao desenvolvimento depois da consulta:

```powershell
git switch codex/v2-multi-eventos
```

Nao devem ser feitas alteracoes diretamente enquanto o repositorio estiver em estado destacado pela tag.

---

## 8. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao da politica de versoes com congelamento da v1.0.0, multi-evento na v2.0.0 e Fluxo de Impressao na v3.0.0 | Preservar a versao atual e evitar mistura entre implementacoes estruturais |
| 2026-08-18 | Atualizacao da v2.0.0 para implementada e validada na branch | Diferenciar conclusao tecnica da criacao da tag de release |
| 2026-08-18 | Registro da implantacao da v2.0.0 com ativacao EDD pendente | Separar deploy concluido do bloqueio externo de DNS e certificado |
