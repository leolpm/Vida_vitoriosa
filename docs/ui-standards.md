# UI Standards

Este arquivo e a referencia obrigatoria quando houver padroes visuais compartilhados entre modulos.

## Regra de uso

- consultar este arquivo antes de definir telas que reutilizam componentes, cores, espacamento ou hierarquia visual
- nao duplicar decisoes visuais em outros documentos quando este arquivo for a fonte canonica
- se o modulo precisar de uma variacao de padrao, registrar apenas a excecao no documento do modulo ou no planejamento correspondente

## Dependencia obrigatoria

Quando uma documentacao de modulo ou planejamento tocar em UI compartilhada, ela deve citar este arquivo como dependencia.

## Formularios publicos por evento

- preservar a estrutura, validacoes e componentes do formulario compartilhado
- variar identidade, banner, textos e cores a partir do evento atual
- usar banner panoramico com `object-fit: cover` sem distorcer a arte
- no celular, centralizar a identidade superior e impedir rolagem horizontal
- manter o campo pesquisavel de participante e a experiencia internacional de telefone em todos os eventos

### EDD

- azul profundo: `#071b73`
- azul principal: `#0757d9`
- azul luminoso: `#38a4f6`
- superficie clara levemente azulada para o formulario
- banner oficial em `public/images/events/edd/`

## Painel administrativo responsivo

- desktop com menu lateral fixo
- celular com menu recolhido e aberto por botao visivel
- nome do evento atual sempre aparente no painel
- seletor superior para alternar apenas entre eventos autorizados

## Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao das regras compartilhadas de UI | Preservar a identidade visual existente nas novas versoes |
| 2026-08-18 | Inclusao das variacoes por evento e regras responsivas | Documentar a identidade EDD e o comportamento validado em desktop e celular |
