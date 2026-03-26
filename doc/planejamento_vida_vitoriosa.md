# Planejamento do Sistema — Vida Vitoriosa

## 1. Contexto

A igreja realiza o retiro **Vida Vitoriosa** e, ao final, cada participante recebe depoimentos enviados por parentes, amigos e pessoas próximas.  
O objetivo do sistema é centralizar o recebimento, armazenamento, organização e geração de **PDFs emocionais e visuais** desses depoimentos.

Este documento foi preparado para orientar a implementação pelo **Codex**.

---

## 2. Objetivo do sistema

Construir um sistema web com:

- **Formulário público** para recebimento de depoimentos
- **Área administrativa** para gestão de participantes e depoimentos
- **Geração de PDF por participante**
- **Layout emocional e visual**, usando a arte oficial do retiro
- **Controle de geração** para evitar repetição automática de depoimentos já exportados

---

## 3. Stack recomendada

Como a implementação será feita com domínio em PHP, a stack recomendada é:

- **PHP 8+**
- **Laravel**
- **Blade + Livewire**
- **Bootstrap** para a interface pública e administrativa
- **MySQL ou PostgreSQL**
- **Storage local no início, com possibilidade de migrar para S3**
- **Geração de PDF por HTML/CSS renderizado no backend**

### Justificativa

Essa stack atende muito bem:

- formulário público
- CRUD administrativo
- autenticação
- upload de imagens
- filtros
- marcação de status
- geração de PDF com regras específicas

---

## 4. Premissas do projeto

- O sistema terá um **link público** para envio de depoimentos
- O destinatário do depoimento será escolhido por **select**, usando a lista oficial de participantes
- A área administrativa será protegida por **login por e-mail com código de acesso enviado por e-mail**
- A área administrativa terá **cadastro e gestão de usuários internos** com nome e e-mail
- Cada depoimento poderá conter **uma foto opcional**
- O PDF final será **emocional, visual e pronto para impressão**
- **Cada depoimento começa em uma nova página**
- O sistema deve registrar os depoimentos já incluídos em PDFs
- A repetição será permitida, mas somente por **ação manual clara do administrador**
- Haverá:
  - uma imagem para o **site público**
  - uma imagem específica para o **layout do PDF**

---

## 5. Caminho de instalação do projeto

> **O Codex deve instalar e desenvolver todo o sistema nesta pasta:**

`C:\Developer\Igreja_vida`


---

## 6. Escopo funcional

## 6.1 Área pública

O sistema deve disponibilizar um formulário público para envio de depoimentos.

### Campos do formulário

#### 1. Nome de quem está enviando o depoimento
- tipo: texto
- obrigatório

#### 2. Participante do retiro
- tipo: select
- obrigatório
- deve listar apenas participantes ativos/cadastrados
- deve evitar digitação manual para não haver divergência de nomes

#### 3. Relação com o participante
- tipo: select
- obrigatório

### Opções sugeridas
- Pai
- Mãe
- Irmão
- Irmã
- Avô
- Avó
- Tio
- Tia
- Primo
- Prima
- Amigo
- Amiga
- Líder
- Pastor
- Cônjuge
- Filho
- Filha
- Outro

#### 4. Campo complementar para “Outro”
- tipo: texto
- exibido apenas quando a opção “Outro” for selecionada

#### 5. Depoimento
- tipo: textarea
- obrigatório

#### 6. Upload de foto
- opcional
- aceitar imagem
- limite: **10 MB**
- formatos aceitos: JPG, JPEG, PNG, WEBP

#### 7. Botão de envio
- ação final de submissão

### Comportamento do formulário
- validação no frontend e backend
- mensagem de sucesso após envio
- proteção contra spam
- idealmente com captcha

---

## 6.2 Área administrativa

A área administrativa deve permitir:

- login de administradores por e-mail e código
- login administrativo por e-mail e código
- cadastro de participantes
- edição de participantes
- ativação/desativação de participantes
- cadastro de usuários administrativos com nome e e-mail
- edição e exclusão de usuários administrativos
- listagem de depoimentos
- filtros por participante, status e data
- visualização individual de depoimento
- download da foto enviada
- geração de PDF por participante
- regeração manual de PDFs
- configuração da imagem do site público
- configuração da imagem do PDF
- interface administrativa com visual moderno, limpo e responsivo

---

## 7. Funcionalidade principal de geração de PDF

## 7.1 Objetivo

Permitir que o administrador selecione um participante e gere um **PDF único** com todos os depoimentos daquele participante.

## 7.2 Regras obrigatórias

- o PDF deve ter **cabeçalho padrão**
- deve usar a **arte oficial do retiro**
- o documento deve ter visual **emocional e editorial**
- **cada depoimento começa em uma nova página**
- incluir:
  - nome do participante
  - nome de quem enviou
  - relação
  - texto do depoimento
  - foto do depoimento, quando existir
- deve haver uma **área reservada para imagem**
- a foto deve ser redimensionada sem distorção
- o PDF deve estar pronto para impressão

## 7.3 Controle de repetição

O sistema deve marcar os depoimentos já incluídos em uma geração de PDF.

### Modo padrão
- gerar apenas depoimentos ainda não exportados

### Modo manual
- permitir ao administrador gerar novamente, incluindo depoimentos já exportados

### Ações recomendadas no admin
- **Gerar PDF (somente novos)**
- **Regerar PDF completo**
- opcional: **Selecionar manualmente depoimentos**

---

## 8. Diretriz visual do PDF

O PDF deve seguir o modelo aprovado pelo usuário:

- uso da arte do retiro no cabeçalho
- aparência de carta/livreto
- composição emocional
- destaque forte no nome do participante
- relação logo abaixo do nome
- texto com boa respiração
- espaço visual para foto
- leitura confortável
- resultado final com aparência acolhedora e não técnica

### Observações de implementação
- a arte do retiro deve ser adaptada como **faixa de cabeçalho**
- evitar usar a arte inteira como fundo total da página
- o layout deve ser montado em HTML/CSS visando renderização para PDF
- a composição precisa funcionar tanto com foto quanto sem foto

---

## 9. Imagens de identidade visual

O sistema deve ter configuração para duas imagens distintas:

### 9.1 Imagem do site público
Usada na página do formulário ou na área de apresentação pública.

Campo sugerido:
- `public_site_image_path`

### 9.2 Imagem do PDF
Usada no cabeçalho/layout do PDF.

Campo sugerido:
- `pdf_header_image_path`

Imagem aprovada para o PDF:
- `C:\Developer\Igreja_vida\ChatGPT Image 25 de mar. de 2026, 15_55_39.png`

---

## 10. Modelo de dados sugerido

## 10.1 Tabela: participants

Campos sugeridos:

- `id`
- `name`
- `display_name`
- `status`
- `retreat_edition`
- `created_at`
- `updated_at`

### Status sugeridos
- active
- inactive

---

## 10.2 Tabela: users

Campos sugeridos:

- `id`
- `name`
- `email`
- `email_verified_at`
- `login_code_hash`
- `login_code_expires_at`
- `last_login_at`
- `is_active`
- `role`
- `created_at`
- `updated_at`

### Papel sugerido
- admin

---

## 10.3 Tabela: testimonials

Campos sugeridos:

- `id`
- `participant_id`
- `sender_name`
- `relationship`
- `relationship_other`
- `message`
- `photo_path`
- `photo_original_name`
- `photo_size`
- `is_pdf_generated`
- `pdf_generated_at`
- `pdf_batch_id`
- `status`
- `created_at`
- `updated_at`

### Status sugeridos
- received
- reviewed
- approved
- archived

---

## 10.4 Tabela: pdf_batches

Campos sugeridos:

- `id`
- `participant_id`
- `generation_mode`
- `generated_by`
- `generated_at`
- `file_path`
- `created_at`
- `updated_at`

### generation_mode
- only_new
- full_regeneration
- manual_selection

---

## 10.5 Tabela: settings

Campos sugeridos:

- `id`
- `key`
- `value`
- `created_at`
- `updated_at`

### Chaves esperadas
- `public_site_image_path`
- `pdf_header_image_path`
- `pdf_footer_text`
- `pdf_cover_phrase`
- `retreat_name`
- `retreat_location`
- `retreat_year`

---

## 11. Fluxos principais

## 11.1 Fluxo público

1. Usuário acessa o link público
2. Preenche nome
3. Escolhe o participante no select
4. Escolhe a relação
5. Escreve o depoimento
6. Envia foto opcional
7. Envia formulário
8. Recebe confirmação de sucesso

---

## 11.2 Fluxo administrativo de gestão

1. Administrador informa o e-mail
2. Sistema envia um código de acesso por e-mail
3. Administrador informa o código recebido
4. Sistema autentica o usuário na área administrativa
5. Administrador acessa o dashboard moderno
6. Acessa lista de participantes
7. Cadastra/edita participantes
8. Gerencia usuários administrativos
9. Consulta depoimentos
10. Filtra por participante
11. Revisa conteúdo, se necessário

---

## 11.3 Fluxo administrativo de geração de PDF

1. Administrador entra na tela de geração de PDF
2. Seleciona um participante
3. Sistema mostra:
   - total de depoimentos
   - quantos já foram exportados
   - quantos ainda são novos
4. Administrador escolhe:
   - gerar somente novos
   - regerar completo
5. Sistema gera o PDF
6. Sistema salva o lote gerado
7. Sistema marca os depoimentos incluídos
8. Administrador faz download do PDF

---

## 12. Requisitos funcionais

### RF01
O sistema deve permitir cadastro e gerenciamento de participantes.

### RF02
O sistema deve exibir os participantes em um select no formulário público.

### RF03
O sistema deve permitir o envio de depoimento com nome do remetente, relação, mensagem e foto opcional.

### RF04
O sistema deve validar upload de imagem com limite máximo de 10 MB.

### RF05
O sistema deve armazenar cada depoimento vinculado a um participante.

### RF06
O sistema deve possuir área administrativa protegida por autenticação.

### RF07
O sistema deve listar, filtrar e visualizar depoimentos.

### RF08
O sistema deve permitir geração de PDF por participante.

### RF09
O PDF deve conter todos os depoimentos do participante selecionado.

### RF10
Cada depoimento deve iniciar em uma nova página do PDF.

### RF11
O PDF deve conter cabeçalho padrão com a arte do retiro.

### RF12
O PDF deve incluir a foto do depoimento, quando existir.

### RF13
O sistema deve marcar depoimentos já incluídos em PDF.

### RF14
O sistema deve permitir regeração manual de PDF completo.

### RF15
O sistema deve permitir configurar separadamente a imagem do site público e a imagem do PDF.

### RF16
O sistema deve permitir cadastro, edição e exclusão de usuários administrativos com nome e e-mail.

### RF17
O sistema deve autenticar a área administrativa por e-mail com código de acesso enviado por e-mail.

---

## 13. Requisitos não funcionais

### RNF01
O sistema deve ser responsivo em dispositivos móveis.

### RNF02
O formulário público deve ter carregamento rápido.

### RNF03
O sistema deve validar dados tanto no frontend quanto no backend.

### RNF04
O upload de imagens deve ser seguro.

### RNF05
A área administrativa deve exigir autenticação.

### RNF06
O PDF deve possuir qualidade adequada para impressão.

### RNF07
O sistema deve ser estruturado para manutenção simples.

### RNF08
O código deve seguir boas práticas do Laravel.

### RNF09
A interface administrativa deve ter aparência moderna, profissional e responsiva.

---

## 14. Regras de negócio

### RN01
O participante do depoimento não deve ser digitado manualmente.

### RN02
Somente participantes ativos devem aparecer no formulário público.

### RN03
A foto é opcional.

### RN04
O depoimento é obrigatório.

### RN05
Cada depoimento pode ser exportado uma ou mais vezes, mas a repetição automática deve ser evitada pelo sistema.

### RN06
A repetição total deve depender de ação manual do administrador.

### RN07
Cada depoimento deve começar em nova página no PDF.

### RN08
O layout do PDF deve manter padrão visual mesmo quando não houver foto.

---

## 15. Diretriz técnica para geração do PDF

A implementação do PDF deve ser baseada em:

- template HTML
- CSS para impressão
- renderização backend

### Recomendação prática
O layout deve ser preparado como página HTML estilizada e depois convertido em PDF.

### Regras de layout
- quebra de página por depoimento
- nome do participante em destaque
- relação abaixo
- cabeçalho com arte
- foto em área fixa ou proporcional
- margens amplas
- aparência emocional

---

## 16. Estrutura sugerida de telas

## 16.1 Área pública
- página inicial do formulário
- página de sucesso

## 16.2 Área administrativa
- login por e-mail
- verificação de código de acesso
- dashboard
- participantes
- cadastro/edição de participante
- usuários administrativos
- cadastro/edição/exclusão de usuário
- depoimentos
- visualização do depoimento
- geração de PDF
- configurações visuais

---

## 17. Estrutura sugerida de rotas

### Públicas
- `/`
- `/depoimentos/enviar`
- `/depoimentos/sucesso`

### Administrativas
- `/admin/login`
- `/admin/login/enviar-codigo`
- `/admin/login/verificar-codigo`
- `/admin/dashboard`
- `/admin/participants`
- `/admin/users`
- `/admin/testimonials`
- `/admin/testimonials/{id}`
- `/admin/pdf`
- `/admin/settings`

---

## 18. Estrutura sugerida do projeto Laravel

```text
[PASTA_DO_PROJETO]/
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
├── public/
├── resources/
│   ├── views/
│   │   ├── public/
│   │   ├── admin/
│   │   ├── pdf/
│   ├── css/
│   ├── js/
├── routes/
├── storage/
│   ├── app/
│   │   ├── public/
│   │   │   ├── testimonials/
│   │   │   ├── settings/
│   │   │   ├── pdf/
├── tests/
└── artisan
```

---

## 19. Estrutura sugerida de módulos

### Módulo 1 — Participantes
- CRUD de participantes
- ativação/desativação
- exibição no formulário público

### Módulo 2 — Depoimentos
- recebimento público
- upload de foto
- vínculo com participante
- listagem e filtros no admin

### Módulo 3 — Configurações visuais
- imagem do site público
- imagem do PDF
- textos auxiliares do PDF

### Módulo 4 — Geração de PDF
- seleção de participante
- geração por novos
- regeração completa
- gravação de lote
- marcação de depoimentos

---

## 20. Requisitos visuais aprovados para o PDF

O PDF deve seguir o conceito abaixo:

- inspirado no modelo visual aprovado
- tom acolhedor
- linguagem editorial
- sensação de carta pessoal
- cabeçalho com identidade do retiro
- nome do participante em destaque principal
- relação em destaque secundário
- texto central bem legível
- foto harmonizada com o layout

---

## 21. Itens que o Codex deve parametrizar

O Codex deve deixar configurável:

- nome do retiro
- local do retiro
- ano/edição
- frase de capa/cabeçalho
- imagem pública
- imagem do PDF
- modo padrão de geração
- limite de upload
- expiração do código de login por e-mail

---

## 22. Critérios de aceite

O sistema será considerado minimamente pronto quando:

1. for possível cadastrar participantes
2. o formulário público listar os participantes corretamente
3. for possível enviar depoimento com foto opcional
4. os depoimentos ficarem vinculados ao participante correto
5. o admin conseguir visualizar os depoimentos
6. o admin conseguir gerar PDF por participante
7. cada depoimento iniciar em nova página
8. a arte do retiro aparecer no cabeçalho do PDF
9. a foto do depoimento aparecer corretamente no PDF, quando houver
10. o sistema marcar os depoimentos já exportados
11. o admin conseguir regerar manualmente um PDF completo
12. o admin conseguir cadastrar, editar e excluir usuários administrativos
13. o login administrativo funcionar com e-mail e código enviado por e-mail

---

## 23. Observações de implementação para o Codex

- priorizar organização limpa em Laravel
- usar migrations e seeders
- usar validações de request
- usar armazenamento padronizado para uploads
- preparar o projeto para manutenção futura
- deixar o template do PDF em pasta própria
- manter separação clara entre visual público, admin e PDF
- deixar a pasta do projeto exatamente no caminho informado pelo usuário
- usar Bootstrap na construção das telas públicas e administrativas
- aplicar um visual moderno no painel administrativo

---

## 24. Entregáveis esperados

- projeto Laravel configurado
- banco modelado
- autenticação administrativa por e-mail e código
- cadastro e gestão de usuários administrativos
- formulário público funcional
- área administrativa funcional
- geração de PDF funcional
- configuração das imagens
- documentação mínima de instalação e execução

---

## 25. Prompt inicial curto para usar com o Codex

Use este prompt inicial, substituindo os caminhos:

```text
Implemente o sistema descrito no arquivo de planejamento em Markdown.

Pasta de instalação do projeto:
C:\Developer\Igreja_vida

Arquivo do planejamento:
"C:\Developer\Igreja_vida\planejamento_vida_vitoriosa.md"

Use Laravel com PHP, siga integralmente o planejamento e crie toda a estrutura do sistema nessa pasta.

Atualize a solução para incluir Bootstrap, login administrativo por e-mail com código de acesso, cadastro de usuários administrativos com nome e e-mail e um painel administrativo com visual moderno.
```

---

## 26. Observação final

Este planejamento já considera a decisão visual aprovada para o PDF:  
**manter o modelo emocional, com cabeçalho baseado na arte do retiro e uma página por depoimento.**
