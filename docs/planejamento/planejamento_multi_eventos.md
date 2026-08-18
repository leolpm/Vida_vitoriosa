# Planejamento do Sistema - Arquitetura Multi-evento

> Status: ativo
> Tipo: planejamento funcional, arquitetural e de testes
> Ordem de execucao: implementar antes do Fluxo de Impressao
> Planejamento dependente: `docs/planejamento/planejamento_fluxo_impressao.md`
> Dependencia visual: `docs/ui-standards.md`

## 1. Contexto

O sistema foi criado inicialmente para o retiro Vida Vitoriosa e deve passar a atender tambem o encontro EDD sem duplicar a aplicacao Laravel.

Os eventos usarao dominios diferentes, mas compartilharao:

- o mesmo codigo
- a mesma instalacao Laravel
- o mesmo banco de dados
- os mesmos usuarios administrativos
- componentes visuais e servicos comuns

Cada evento deve manter isolados:

- participantes
- depoimentos
- configuracoes
- imagens
- PDFs e historicos existentes
- relatorios
- dashboard
- futuros fluxos de impressao

### Dominios de producao

| Dominio | Evento |
|---|---|
| `vidavitoriosa.atitudelaranja.com` | Vida Vitoriosa |
| `edd.atitudelaranja.com` | EDD |

### Dominios locais

| Dominio local | Evento |
|---|---|
| `vidavitoriosa.atitudelaranja.test` | Vida Vitoriosa |
| `edd.atitudelaranja.test` | EDD |

O dominio da requisicao sera a fonte primaria para identificar o evento atual.

---

## 2. Objetivos

- atender Vida Vitoriosa e EDD na mesma aplicacao
- permitir novos eventos sem duplicar codigo
- apresentar formulario, textos e imagens especificos por evento
- impedir mistura de dados entre eventos
- manter administracao e autenticacao compartilhadas
- preparar o sistema para o futuro Fluxo de Impressao
- permitir testes locais reproduzindo os subdominios reais
- manter rastreabilidade da migracao dos dados atuais

---

## 3. Decisoes arquiteturais aprovadas

| Tema | Decisao |
|---|---|
| Aplicacao | Uma unica aplicacao Laravel |
| Banco | Um unico banco de dados |
| Identificacao do evento | Dominio da requisicao |
| Dominios | Multiplos dominios por evento e ambiente |
| Usuarios administrativos | Globais |
| Participantes | Sempre vinculados a um evento |
| Depoimentos | Sempre vinculados a um evento |
| Configuracoes | Gerais ou especificas por evento |
| Arquivos | Separados em diretorios por evento |
| Sessao | Compartilhavel entre subdominios do mesmo ambiente |
| Evento desconhecido | Falha fechada, sem carregar dados de outro evento |
| Reset | Limitado ao evento atual por padrao |
| Fluxo de Impressao | Implementado depois desta arquitetura |

### Regra principal

O evento nao pode ser escolhido por um campo oculto enviado pelo navegador. O backend deve resolver o evento pelo host e validar todas as relacoes com esse contexto.

Parametros de URL, formularios e sessao podem auxiliar a navegacao, mas nunca substituir a validacao do dominio.

---

## 4. Cadastro de eventos e dominios

## 4.1 Evento

Cada evento deve possuir:

- nome
- slug
- status ativo ou inativo
- nome exibido no formulario
- termo usado para o destinatario
- configuracoes visuais e textuais
- timestamps

Eventos iniciais:

| Slug | Nome |
|---|---|
| `vida-vitoriosa` | Vida Vitoriosa |
| `edd` | EDD |

## 4.2 Multiplos dominios por evento

O dominio nao deve ficar como uma unica coluna na tabela de eventos. Deve existir uma entidade de dominios para permitir producao, ambiente local e futura homologacao.

Campos planejados:

- event_id
- host
- ambiente
- esquema HTTP ou HTTPS
- porta opcional
- dominio principal do ambiente
- status ativo ou inativo
- timestamps

Exemplo:

| Evento | Ambiente | Host | Esquema | Porta | Principal |
|---|---|---|---|---:|---|
| Vida Vitoriosa | production | `vidavitoriosa.atitudelaranja.com` | HTTPS | - | Sim |
| EDD | production | `edd.atitudelaranja.com` | HTTPS | - | Sim |
| Vida Vitoriosa | local | `vidavitoriosa.atitudelaranja.test` | HTTP | 8888 | Sim |
| EDD | local | `edd.atitudelaranja.test` | HTTP | 8888 | Sim |

### Regras

- o host deve ser unico dentro do ambiente
- o host deve ser salvo sem protocolo e sem caminho
- a porta nao participa da identificacao principal do evento
- apenas dominios ativos podem resolver eventos
- cada evento deve possuir no maximo um dominio principal por ambiente
- links absolutos devem usar o dominio principal do ambiente atual

---

## 5. Resolucao do evento

## 5.1 Componente central

Criar um resolvedor de evento executado no inicio de cada requisicao web.

Responsabilidades:

1. obter o host da requisicao
2. normalizar o host
3. procurar um dominio ativo no ambiente atual
4. carregar o evento vinculado
5. disponibilizar o evento para controllers, views e servicos
6. aplicar o escopo de dados

O evento resolvido deve ficar disponivel por um servico de contexto, por exemplo `CurrentEvent`, registrado por requisicao.

## 5.2 Falha fechada

Se o dominio nao estiver cadastrado:

- nao selecionar automaticamente o Vida Vitoriosa
- nao carregar participantes ou depoimentos
- responder com pagina de evento nao encontrado
- registrar o host desconhecido no log

## 5.3 Rotas

As mesmas rotas podem atender todos os eventos.

Exemplos:

```text
GET  /                         formulario publico do evento atual
POST /depoimentos/enviar       envio para o evento atual
GET  /admin/login              login administrativo
GET  /admin/dashboard          dashboard do evento atual
GET  /admin/participants       participantes do evento atual
GET  /admin/testimonials       depoimentos do evento atual
GET  /admin/reports            relatorios do evento atual
GET  /admin/settings           configuracoes do evento atual
```

Nao devem ser criadas copias como `/edd/participants` e `/vida-vitoriosa/participants`.

## 5.4 Escopo automatico

Entidades pertencentes a um evento devem usar um mecanismo compartilhado de escopo.

Regras:

- preencher `event_id` a partir do contexto atual na criacao
- filtrar consultas web pelo evento atual
- falhar quando uma requisicao web exigir evento e nenhum estiver resolvido
- exigir contexto explicito em comandos, filas e tarefas agendadas
- permitir consultas globais somente em servicos administrativos autorizados

Mesmo com escopo automatico, validacoes de relacionamento continuam obrigatorias.

---

## 6. Modelo de dados

## 6.1 Novas entidades

### `events`

- id
- name
- slug unico
- status
- created_at
- updated_at

### `event_domains`

- id
- event_id
- host
- environment
- scheme
- port nullable
- is_primary
- is_active
- created_at
- updated_at

### `event_settings`

- id
- event_id
- key
- value nullable
- created_at
- updated_at

Restricao unica:

```text
event_id + key
```

### `event_user`

Associacao opcional para limitar administradores a determinados eventos.

- event_id
- user_id
- role no evento
- is_active
- timestamps

## 6.2 Tabelas existentes que receberao `event_id`

- participants
- testimonials
- pdf_batches

Tabelas futuras do Fluxo de Impressao tambem devem possuir `event_id`.

## 6.3 Configuracoes globais

A tabela atual de configuracoes pode permanecer para parametros realmente globais, como:

- expiracao do codigo de login
- nome tecnico da aplicacao
- parametros gerais de seguranca

Configuracoes de identidade, formulario, encerramento e impressao devem ir para `event_settings`.

## 6.4 Integridade obrigatoria

- participante deve possuir evento
- depoimento deve possuir evento
- depoimento e participante devem pertencer ao mesmo evento
- lote PDF e participante devem pertencer ao mesmo evento
- dominio deve apontar para um evento existente
- slug do evento deve ser unico
- exclusao de evento nao deve apagar dados automaticamente
- indices devem incluir `event_id` nas consultas frequentes

## 6.5 Duplicidade de participantes

A verificacao de duplicidade deve ser feita dentro do evento.

O mesmo nome pode existir em eventos diferentes.

Exemplo valido:

```text
Ana Oliveira - Vida Vitoriosa
Ana Oliveira - EDD
```

---

## 7. Migracao dos dados atuais

## 7.1 Criacao do evento Vida Vitoriosa

Antes de tornar `event_id` obrigatorio:

1. criar o evento Vida Vitoriosa
2. cadastrar os dominios de producao e local
3. atribuir todos os participantes atuais ao Vida Vitoriosa
4. atribuir todos os depoimentos atuais ao Vida Vitoriosa
5. atribuir lotes de PDF existentes ao Vida Vitoriosa
6. copiar configuracoes visuais atuais para `event_settings`
7. validar contagens antes e depois
8. tornar `event_id` obrigatorio

## 7.2 Criacao do evento EDD

Depois da migracao do Vida Vitoriosa:

- criar o evento EDD
- cadastrar os dominios do EDD
- criar configuracoes iniciais
- manter participantes e depoimentos inicialmente vazios
- importar participantes somente pelo painel do EDD

## 7.3 Verificacoes de migracao

Registrar e comparar:

- total de participantes
- total de depoimentos
- total de depoimentos com foto
- total por status
- total de lotes PDF
- total de arquivos existentes
- chaves de configuracao migradas

Nenhum registro existente pode ficar sem evento depois da migracao.

## 7.4 Reversao

A implementacao deve prever rollback tecnico das migrations antes da ativacao em producao.

O rollback nao deve ser usado depois que dados reais do EDD forem cadastrados sem um plano de preservacao desses dados.

---

## 8. Configuracoes por evento

Cada evento deve controlar:

| Configuracao | Vida Vitoriosa | EDD |
|---|---|---|
| Nome exibido | Vida Vitoriosa | EDD |
| Termo do destinatario | Participante | Liderado |
| Titulo do formulario | Texto atual | Texto especifico do EDD |
| Texto de apresentacao | Texto atual | Texto especifico do EDD |
| Aviso de surpresa | Texto atual | Texto especifico do EDD |
| Opcoes de relacao | Relacoes atuais | Lider, Supervisor, Pastor, Coordenador e Outro |
| Encerramento | Independente | Independente |
| Imagem publica | Independente | Independente |
| Imagem de PDF ou impressao | Independente | Independente |
| Texto de rodape | Independente | Independente |

## 8.1 Texto inicial do EDD

### Titulo

```text
Envie uma mensagem especial para seu liderado
```

### Apresentacao

```text
Lider ou supervisor, envie uma mensagem de carinho, gratidao e impulsionamento para o seu liderado que esta participando do EDD. Sua mensagem sera entregue de forma especial a quem voce ama.
```

### Aviso de surpresa

```text
Esta mensagem e uma surpresa para o seu liderado. Nao conte que voce escreveu esta mensagem. Ela sera entregue de forma especial durante o EDD e precisa permanecer em segredo.
```

### Rotulo de selecao

```text
Para qual liderado?
```

## 8.2 Status do evento

Evento inativo:

- nao recebe novos depoimentos
- mostra mensagem amigavel no formulario publico
- permanece disponivel para consulta administrativa autorizada
- nao e removido do banco

O encerramento por data e horario continua independente do status do evento.

---

## 9. Formulario publico

O formulario deve reutilizar o layout atual e carregar dinamicamente:

- nome do evento
- titulo
- apresentacao
- aviso de surpresa
- imagem
- termo do destinatario
- opcoes de relacao
- participantes ativos do evento
- prazo de encerramento

## 9.1 Envio

Ao receber o POST:

- resolver novamente o evento pelo dominio
- verificar se o evento esta ativo
- verificar o encerramento
- validar que o participante pertence ao evento
- ignorar qualquer `event_id` enviado pelo navegador
- salvar o depoimento com o evento resolvido
- armazenar a imagem no diretorio do evento

## 9.2 Participantes

O seletor pesquisavel deve listar apenas participantes ativos do evento atual.

Nomes iguais em eventos diferentes nao podem aparecer juntos.

## 9.3 Encerramento

Cada evento possui seu proprio prazo.

Quando encerrado:

- ocultar o formulario
- exibir a mensagem amigavel existente
- bloquear tambem o POST
- nao afetar o outro evento

---

## 10. Area administrativa

## 10.1 Identificacao do evento

Todas as telas devem mostrar o evento atual no cabecalho ou em area equivalente.

## 10.2 Troca de evento

O seletor administrativo deve redirecionar para o dominio principal do outro evento no ambiente atual.

Exemplo local:

```text
http://edd.atitudelaranja.test:8888/admin/dashboard
http://vidavitoriosa.atitudelaranja.test:8888/admin/dashboard
```

O caminho atual pode ser preservado quando existir no destino.

## 10.3 Dashboard

Os indicadores devem considerar somente o evento atual:

- participantes
- participantes ativos
- depoimentos
- aprovados
- aprovados sem PDF
- pendentes
- lotes PDF

Usuarios administrativos continuam sendo uma metrica global e devem ser identificados como tal.

## 10.4 Participantes

- listar somente participantes do evento
- cadastrar no evento atual
- importar planilha para o evento atual
- baixar modelo sem exigir coluna de evento
- verificar duplicidade dentro do evento
- excluir somente registros do evento atual

## 10.5 Depoimentos

- listar somente depoimentos do evento
- filtrar participantes do evento
- atualizar status somente dentro do evento
- baixar foto somente depois de validar o evento

## 10.6 PDFs atuais

Enquanto a geracao atual existir:

- listar lotes do evento atual
- gerar PDF somente com depoimentos do evento atual
- usar imagem e rodape do evento
- impedir download cruzado entre dominios sem permissao geral

## 10.7 Relatorios

- filtrar dados pelo evento atual
- usar o nome do evento no cabecalho de impressao
- incluir o slug do evento no nome do arquivo Excel
- manter filtros atuais

## 10.8 Configuracoes

A tela deve separar visualmente:

- configuracoes gerais
- configuracoes do evento atual

O usuario deve saber qual evento esta alterando antes de salvar.

---

## 11. Reset e operacoes destrutivas

O reset atual precisa ser alterado antes da ativacao multi-evento.

### Regra padrao

O botao deve se chamar **Resetar dados do evento** e apagar apenas:

- participantes do evento atual
- depoimentos do evento atual
- lotes PDF do evento atual
- fotos dos depoimentos do evento atual
- arquivos gerados do evento atual

Nao deve apagar:

- o evento
- configuracoes do outro evento
- participantes do outro evento
- usuarios administrativos
- arquivos do outro evento

### Confirmacao

A modal deve mostrar o nome do evento e exigir uma confirmacao especifica.

Exemplo:

```text
RESETAR EDD
```

Um reset global nao deve ficar disponivel na interface comum nesta primeira versao.

---

## 12. Usuarios, login e sessao

## 12.1 Usuarios

Os usuarios administrativos continuam globais.

Inicialmente, os usuarios atuais podem receber acesso aos dois eventos. A estrutura deve permitir restricao futura por `event_user`.

## 12.2 Login por codigo

O login continua por e-mail e codigo.

O sistema deve lembrar o dominio onde o login foi iniciado e retornar o usuario ao mesmo evento depois da verificacao.

## 12.3 Sessao compartilhada

Recomendacao:

- producao: cookie compartilhado em `.atitudelaranja.com`
- local: cookie compartilhado em `.atitudelaranja.test`

O compartilhamento permite alternar entre eventos sem novo login.

Mesmo autenticado, o usuario precisa possuir permissao para o evento acessado.

---

## 13. Arquivos e armazenamento

Estrutura planejada:

```text
storage/app/public/events/vida-vitoriosa/settings
storage/app/public/events/vida-vitoriosa/testimonials
storage/app/public/events/vida-vitoriosa/pdf

storage/app/public/events/edd/settings
storage/app/public/events/edd/testimonials
storage/app/public/events/edd/pdf
```

Regras:

- novos arquivos devem usar o slug do evento
- caminhos antigos devem ser migrados ou permanecer legiveis por compatibilidade
- exclusao deve validar o evento antes de remover o arquivo
- nomes de arquivo fornecidos pelo usuario nao devem definir o diretorio final
- previews devem usar URLs relativas ou um gerador de URL sensivel ao dominio

---

## 14. Ambiente local com dominios de teste

## 14.1 Estrategia escolhida

Usar o dominio reservado `.test`, com entradas no arquivo `hosts` do Windows.

Essa estrategia reproduz o comportamento dos subdominios reais e permite testar:

- resolucao por host
- cookies compartilhados
- troca de evento
- links absolutos
- bloqueio de dominio incorreto

## 14.2 Arquivo hosts do Windows

Arquivo:

```text
C:\Windows\System32\drivers\etc\hosts
```

Entradas necessarias:

```text
127.0.0.1 vidavitoriosa.atitudelaranja.test
127.0.0.1 edd.atitudelaranja.test
127.0.0.1 evento-invalido.atitudelaranja.test
```

A terceira entrada existe somente para testar o comportamento de dominio desconhecido.

Depois da alteracao, executar em terminal administrativo:

```powershell
ipconfig /flushdns
```

O arquivo `hosts` nao aceita portas. A porta deve continuar na URL do navegador.

## 14.3 Servidor Laravel

O projeto ja usa a porta padrao `8888` para `php artisan serve`.

Comando esperado:

```powershell
php artisan serve
```

Enderecos locais:

```text
http://vidavitoriosa.atitudelaranja.test:8888/
http://edd.atitudelaranja.test:8888/
```

Se o servidor nao aceitar os hosts locais, usar explicitamente:

```powershell
php artisan serve --host=127.0.0.1 --port=8888
```

## 14.4 Configuracao local sugerida

```env
APP_ENV=local
APP_URL=http://vidavitoriosa.atitudelaranja.test:8888
SESSION_DOMAIN=.atitudelaranja.test
SESSION_SECURE_COOKIE=false
```

`APP_URL` funciona apenas como fallback tecnico. A selecao do evento e a geracao de URLs do evento nao devem depender exclusivamente dela.

Depois de alterar o ambiente:

```powershell
php artisan optimize:clear
```

## 14.5 HTTPS local

HTTPS local nao e obrigatorio para os testes funcionais iniciais.

Os seguintes pontos precisam de validacao posterior em homologacao ou producao com HTTPS real:

- cookie `Secure`
- redirecionamento HTTP para HTTPS
- comportamento atras de proxy
- certificados dos dois subdominios
- envio do formulario em Safari e iPhone sem alerta de conexao insegura

## 14.6 Teste em celular fisico

O arquivo `hosts` do Windows afeta apenas o computador local.

Para testar em um celular fisico, usar uma destas alternativas:

- DNS local configurado no roteador ou servidor da rede
- ambiente de homologacao com subdominios reais e HTTPS
- tunel temporario com dois hosts distintos e configuracao equivalente

Durante o desenvolvimento, a validacao responsiva principal pode ser feita pela emulacao de dispositivos no navegador. Antes da producao, deve haver pelo menos um teste em celular fisico usando HTTPS.

---

## 15. Dados de teste multi-evento

Criar um seeder especifico para validacao multi-evento.

### Vida Vitoriosa

- participantes ativos e inativos
- depoimentos em todos os status
- depoimentos com e sem imagem
- lotes PDF existentes
- encerramento futuro

### EDD

- participantes ativos e inativos
- depoimentos em todos os status
- depoimentos com e sem imagem
- imagem e textos proprios
- encerramento diferente do Vida Vitoriosa

### Casos intencionais

- mesmo nome de participante nos dois eventos
- mesmo nome de autor nos dois eventos
- participante com depoimentos apenas em um evento
- configuracoes visuais totalmente diferentes
- um evento encerrado e outro aberto
- administrador com acesso aos dois eventos
- futuro administrador com acesso a apenas um evento

Esses casos existem para revelar vazamento de dados e uso incorreto de configuracoes globais.

---

## 16. Estrategia de testes automatizados

Nenhuma fase deve ser considerada concluida apenas por teste manual.

## 16.1 Testes unitarios

Cobrir:

- normalizacao de host
- resolucao do evento
- rejeicao de host desconhecido
- selecao do dominio principal por ambiente
- geracao de URL por evento
- leitura de configuracao geral e por evento
- calculo de encerramento por evento
- construcao do caminho de armazenamento

## 16.2 Testes de feature Laravel

As requisicoes devem informar explicitamente o host no teste.

Cenarios:

- formulario do Vida Vitoriosa mostra textos corretos
- formulario do EDD mostra textos corretos
- participantes do EDD nao aparecem no Vida Vitoriosa
- participantes do Vida Vitoriosa nao aparecem no EDD
- envio salva `event_id` correto
- envio para participante de outro evento e rejeitado
- evento encerrado bloqueia GET e POST do formulario
- encerramento de um evento nao bloqueia o outro
- dashboard apresenta contagens do evento atual
- filtros administrativos permanecem isolados
- relatorios e Excel usam somente o evento atual
- PDF atual usa configuracoes e dados do evento atual
- reset apaga somente o evento atual
- dominio desconhecido nao carrega evento padrao

Os testes nao dependem do arquivo `hosts`; o host pode ser informado diretamente na requisicao de teste.

## 16.3 Testes de banco e integridade

- migrations executam sobre banco vazio
- migrations preservam dados existentes
- todos os registros antigos recebem Vida Vitoriosa
- `event_id` nao fica nulo depois da migracao
- chaves estrangeiras impedem relacoes invalidas
- indices compostos funcionam por evento
- mesmo participante pode existir em eventos diferentes
- configuracao usa chave unica por evento

## 16.4 Testes de autenticacao e permissao

- login iniciado no EDD retorna ao EDD
- login iniciado no Vida Vitoriosa retorna ao Vida Vitoriosa
- sessao compartilhada permite alternar dominios
- usuario sem permissao recebe bloqueio adequado
- alterar host nao concede acesso ao evento
- CSRF continua valido no fluxo configurado

## 16.5 Testes de arquivos

- upload do EDD vai para diretorio do EDD
- upload do Vida Vitoriosa vai para diretorio do Vida Vitoriosa
- preview abre no dominio atual
- exclusao nao remove arquivo de outro evento
- reset remove apenas diretorio do evento atual
- imagens antigas do Vida Vitoriosa continuam acessiveis

## 16.6 Testes de importacao e exportacao

- importacao cadastra participantes no evento atual
- planilha nao precisa receber `event_id`
- duplicidade e verificada dentro do evento
- acentos permanecem corretos
- Excel exportado possui apenas dados do evento
- nome do arquivo identifica o evento

## 16.7 Testes de compatibilidade com o Fluxo de Impressao

Antes de considerar o multi-evento concluido, validar que a arquitetura permite:

- vincular futuros fluxos a `event_id`
- gerar links no dominio do evento
- autorizar equipe por evento
- carregar identidade visual na impressao
- calcular participantes criticos por evento
- auditar operacoes com identificacao do evento

---

## 17. Testes no navegador e validacao visual

Os testes visuais fazem parte obrigatoria da implementacao. Depois dos testes automatizados, o sistema deve ser aberto em navegador real nos dois dominios locais.

## 17.1 Navegadores e tamanhos

Validacao minima:

| Perfil | Tamanho sugerido |
|---|---|
| Desktop amplo | 1440 x 900 |
| Notebook | 1366 x 768 |
| Tablet | 768 x 1024 |
| Celular | 390 x 844 |
| Celular compacto | 375 x 667 |

Testar inicialmente em Chromium ou Chrome. Antes da producao, validar tambem Safari em iPhone fisico por causa de upload, telefone e HTTPS.

## 17.2 Roteiro visual publico

Abrir lado a lado:

```text
http://vidavitoriosa.atitudelaranja.test:8888/
http://edd.atitudelaranja.test:8888/
```

Verificar:

- identidade visual correta
- titulo e texto corretos
- imagem correta do evento
- termo Participante no Vida Vitoriosa
- termo Liderado no EDD
- aviso de surpresa correto
- seletor mostra apenas pessoas do evento
- busca do seletor funciona
- telefone troca mascara conforme o pais
- validacoes aparecem em portugues
- upload mostra e salva a imagem corretamente
- formulario encerrado mostra mensagem centralizada
- nenhum elemento fica cortado no celular
- topo publico permanece centralizado no celular
- nao existem imagens quebradas
- console do navegador sem erros relevantes
- rede sem respostas 404 ou 500 inesperadas

## 17.3 Roteiro visual administrativo

Em cada dominio:

- realizar login por codigo
- confirmar identificacao do evento atual
- abrir e recolher o menu no celular
- alternar entre eventos
- verificar se a troca redireciona o dominio
- conferir dashboard e tooltips
- listar e pesquisar participantes
- importar participantes
- listar e filtrar depoimentos
- abrir fotos
- gerar e baixar PDF atual enquanto existir
- abrir relatorios
- imprimir relatorios pelo navegador
- exportar Excel
- editar configuracoes do evento
- confirmar que a outra configuracao nao mudou
- abrir a modal de reset sem executar exclusao real no teste visual comum

## 17.4 Testes visuais de isolamento

- cadastrar participante com mesmo nome nos dois eventos
- confirmar que cada dominio mostra apenas seu registro
- cadastrar depoimento no EDD e verificar ausencia no Vida Vitoriosa
- alterar a imagem do EDD e confirmar que o Vida Vitoriosa permanece igual
- encerrar apenas o EDD e confirmar que o Vida Vitoriosa continua aberto
- trocar entre eventos mantendo a autenticacao quando configurada
- abrir dominio invalido e confirmar que nenhum dado real aparece

## 17.5 Evidencias

Durante a implementacao, registrar capturas de tela dos cenarios principais:

- formulario desktop dos dois eventos
- formulario mobile dos dois eventos
- dashboard dos dois eventos
- configuracoes dos dois eventos
- evento encerrado
- dominio desconhecido
- seletor administrativo de evento

As evidencias locais podem ficar em diretorio temporario e nao precisam ser versionadas. Problemas encontrados devem ser corrigidos e testados novamente antes da conclusao.

## 17.6 Criterios de falha visual

O teste visual falha se houver:

- texto ou imagem de evento incorreto
- dados misturados
- imagem quebrada
- erro JavaScript que afete a operacao
- requisicao 500
- formulario inutilizavel no celular
- menu administrativo bloqueando o conteudo
- campo fora da tela
- botao sem acesso por teclado ou toque
- contraste ou legibilidade significativamente inferiores ao sistema atual

---

## 18. Sequencia local de validacao

Ordem planejada:

1. configurar os hosts locais
2. preparar `.env`
3. executar migrations
4. executar o seeder multi-evento
5. limpar caches
6. iniciar Laravel na porta 8888
7. executar testes unitarios e de feature
8. abrir os dois dominios no navegador
9. executar roteiro desktop
10. executar roteiro responsivo
11. capturar evidencias
12. corrigir problemas
13. repetir testes afetados
14. validar em celular fisico no ambiente HTTPS antes da producao

Comandos esperados:

```powershell
php artisan migrate
php artisan db:seed
php artisan optimize:clear
php artisan serve
```

O comando de testes sera definido conforme a instalacao do PHPUnit do projeto. A implementacao deve corrigir a ausencia atual do comando `php artisan test` ou documentar um executavel equivalente antes de considerar a fase concluida.

---

## 19. Implantacao em producao

## 19.1 Preparacao

- backup do banco
- backup de `storage`
- confirmar DNS dos dois subdominios
- instalar certificados HTTPS
- configurar cookies seguros
- cadastrar dominios de producao
- executar migracao primeiro em homologacao
- comparar contagens antes e depois

## 19.2 Ativacao

- manter Vida Vitoriosa funcionando durante a migracao
- ativar resolucao por dominio
- validar painel do Vida Vitoriosa
- validar formulario do Vida Vitoriosa
- ativar EDD inicialmente sem participantes
- cadastrar configuracoes e imagens do EDD
- importar participantes do EDD
- abrir formulario do EDD

## 19.3 Verificacao posterior

- acompanhar logs de dominio desconhecido
- acompanhar erros 500
- verificar uploads
- testar login nos dois dominios
- testar um depoimento real controlado em cada evento
- confirmar contagens administrativas

---

## 20. Riscos e controles

| Risco | Controle |
|---|---|
| Consulta sem filtro de evento | Contexto central, escopo compartilhado e testes de isolamento |
| Formulario alterar `event_id` | Ignorar campo do cliente e resolver pelo dominio |
| Reset apagar outro evento | Reset limitado ao evento atual e confirmacao com nome |
| Imagem aparecer no evento errado | Diretorios por slug e validacao de propriedade |
| `APP_URL` gerar links errados | Gerador de URL baseado em `event_domains` |
| Sessao conceder acesso indevido | Permissao por evento validada em cada requisicao |
| Dominio desconhecido carregar Vida Vitoriosa | Falha fechada sem fallback de evento |
| Migracao perder dados atuais | Backup, contagens e migracao em etapas |
| Testes locais nao reproduzirem subdominios | Dominios `.test` no arquivo hosts |
| Celular nao resolver hosts do PC | Homologacao HTTPS ou DNS local |

---

## 21. Criterios de aceite

A implementacao multi-evento sera considerada concluida quando:

- os dois dominios de producao estiverem cadastrados
- os dois dominios locais funcionarem na porta 8888
- o evento for resolvido exclusivamente pelo host
- dominio desconhecido falhar sem revelar dados
- dados atuais estiverem associados ao Vida Vitoriosa
- EDD possuir configuracoes proprias
- formularios mostrarem textos, imagens e termos corretos
- participantes e depoimentos permanecerem isolados
- dashboard, relatorios, PDFs e configuracoes respeitarem o evento
- importacao e exportacao respeitarem o evento
- encerramento funcionar de forma independente
- arquivos forem armazenados por evento
- reset apagar somente o evento atual
- login funcionar nos dois dominios
- alternancia de evento redirecionar o dominio
- testes automatizados passarem
- testes visuais desktop e mobile forem executados
- nao houver erros relevantes no console ou rede
- pelo menos um teste em celular fisico com HTTPS for concluido antes da producao
- a base estiver pronta para receber o Fluxo de Impressao

---

## 22. Fases de implementacao

### Fase 1 - Fundacao

- criar eventos e dominios
- criar resolvedor e contexto atual
- criar configuracoes por evento
- adicionar escopo compartilhado

### Fase 2 - Migracao

- criar Vida Vitoriosa
- migrar dados atuais
- cadastrar EDD
- validar contagens e integridade

### Fase 3 - Formulario publico

- carregar identidade e textos por evento
- adaptar participantes, envio, upload e encerramento
- criar formulario do EDD

### Fase 4 - Administracao

- adaptar dashboard
- adaptar participantes e importacao
- adaptar depoimentos
- adaptar PDFs atuais
- adaptar relatorios
- adaptar configuracoes e reset
- adicionar seletor de evento

### Fase 5 - Autenticacao e arquivos

- compartilhar sessao entre subdominios
- adicionar permissao por evento
- reorganizar armazenamento
- preservar compatibilidade dos arquivos existentes

### Fase 6 - Testes e validacao visual

- criar dados de teste
- executar testes automatizados
- testar os dominios locais no navegador
- testar viewports desktop, tablet e celular
- corrigir erros visuais e funcionais
- preparar homologacao HTTPS

### Fase 7 - Producao e documentacao canonica

- implantar com backup
- validar os dois subdominios
- atualizar documentacao dos modulos implementados
- registrar historico da versao
- liberar inicio do Fluxo de Impressao

---

## 23. Relacao com o Fluxo de Impressao

O planejamento `docs/planejamento/planejamento_fluxo_impressao.md` depende desta implementacao.

O Fluxo de Impressao deve reutilizar:

- evento atual resolvido pelo dominio
- dominios por ambiente
- configuracoes por evento
- permissoes por evento
- armazenamento separado
- geracao de URL por evento
- estrategia de testes automatizados e visuais

Nenhuma tabela futura do Fluxo de Impressao deve criar uma segunda forma de identificar o evento.

---

## 24. Historico de alteracoes

| Data | Alteracao | Motivo |
|---|---|---|
| 2026-08-18 | Criacao do planejamento multi-evento com arquitetura por dominio, migracao, seguranca, ambiente local, testes automatizados e validacao visual no navegador | Consolidar a implementacao do Vida Vitoriosa e EDD antes do desenvolvimento do Fluxo de Impressao |
