# Deploy no Railway

## Por que o build falhou

O Railpack (autodetecção do Railway) olhou a raiz do repositório, encontrou
`api/` e `client/` mas nenhum `package.json` ou `composer.json` no topo, e
desistiu. Não é falta de configuração de ambiente — é que este repositório não
tem um "app" na raiz: tem dois, mais três papéis de processo.

A correção é dizer ao Railway para usar os Dockerfiles em vez de adivinhar. É o
que os arquivos em `railway/` fazem.

## Cinco serviços, não quatro

> **Correção importante.** Se você leu que dava para descartar o `ingress`,
> desconsidere. O `docker/Dockerfile.api` termina em `php-fpm`, que fala
> **FastCGI na porta 9000 — não HTTP**. O Railway roteia HTTP. Sem um nginx na
> frente, o serviço da API responde 502 para sempre, e o erro não diz o motivo.
>
> O `ingress` também é quem separa `/api`, `/open`, `/forms/assets` (que vão
> para o PHP) de todo o resto (que vai para o Nuxt). Os dois precisam responder
> no mesmo host, senão cookie de sessão e SSO quebram.

| Serviço | Config as code | Domínio público | Escuta |
|---|---|---|---|
| `ingress` | `railway/ingress.json` | ✅ `forms.mentorfy.io` | `$PORT` |
| `api` | `railway/api.json` | ❌ | `[::]:9000` |
| `client` | `railway/client.json` | ❌ | `[::]:3000` |
| `worker` | `railway/worker.json` | ❌ | — |
| `scheduler` | `railway/scheduler.json` | ❌ | — |

Mais os plugins **PostgreSQL** e **Redis**.

Os cinco apontam para o **mesmo repositório**. O que muda é o caminho do
config-as-code (Settings → Config as Code) e as variáveis.

## Nome dos serviços importa

Os defaults do `Dockerfile.ingress` são `api.railway.internal:9000` e
`client.railway.internal:3000`. **Eles quase nunca servem.** O Railway deriva o
host privado do nome do serviço, então "OpnForm API" vira
`opnform.railway.internal` e "OpnForm Cliente" vira
`opnform-cliente.railway.internal`. Confira o `RAILWAY_PRIVATE_DOMAIN` de cada
serviço e ajuste `OPNFORM_API_HOST` e `OPNFORM_CLIENT_HOST` no `ingress`.

O erro aqui é silencioso na origem: o nginx só reclama quando chega uma
requisição, com `could not be resolved` no log, e o navegador vê um 502 igual ao
de qualquer outra falha.

Na rede privada do Railway a porta **não** é inferida — tem que vir explícita no
host, e é por isso que ela aparece nessas duas variáveis.

## O startCommand substitui o ENTRYPOINT

O `Dockerfile.api` define `ENTRYPOINT ["/usr/local/bin/opnform-entrypoint"]` com
`CMD ["php-fpm"]`. No Railway, o `startCommand` do config-as-code substitui os
**dois** — não só o `CMD`. Um `startCommand: "php-fpm"` sobe o container sem
nunca passar pelo entrypoint: sem migration, sem `prep_storage`, e ignorando
`PHP_FPM_LISTEN` em silêncio.

E o serviço fica **verde**, porque o `wait_for_db` nunca chega a rodar. Foi assim
que a `api` ficou "Online" por quase uma hora sem existir banco no projeto.

Por isso `railway/api.json` não define `startCommand` (cai no `CMD` do
Dockerfile), e `worker.json` e `scheduler.json` chamam o entrypoint
explicitamente antes do `artisan`.

## Variáveis

### Compartilhadas por `api`, `worker` e `scheduler`

```bash
APP_ENV=production
APP_KEY=                      # php artisan key:generate --show
JWT_SECRET=                   # php artisan jwt:secret --show (64 chars aleatórios)
FRONT_API_SECRET=             # mesmo valor de NUXT_API_SECRET no client
JWT_TTL=43200                 # 30 dias
JWT_REFRESH_TTL=44640         # 31 dias, sempre acima do TTL
APP_URL=https://forms.mentorfy.io
SELF_HOSTED=true
CASHIER_KEY=

LOG_CHANNEL=errorlog
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

REDIS_HOST=${{Redis.REDISHOST}}
REDIS_PORT=${{Redis.REDISPORT}}
REDIS_PASSWORD=${{Redis.REDISPASSWORD}}
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

PHP_MEMORY_LIMIT=1G
PHP_MAX_EXECUTION_TIME=600
PHP_UPLOAD_MAX_FILESIZE=64M
PHP_POST_MAX_SIZE=64M
```

Mais o bloco do `opnform-secrets/env-fork.txt` (chave pública do SSO, conta de
serviço, callback, API key).

`APP_KEY` e `JWT_SECRET` precisam ser **iguais nos três serviços**: sessões e
tokens emitidos pela `api` são lidos pelo `worker`.

Esquecer o `JWT_SECRET` é a falha mais cara da lista, porque o sintoma não
aponta para ele. O grupo de middleware `api` começa com `throttle`, que chama
`$request->user()` e resolve o guard JWT em **toda** requisição, autenticada ou
não. Sem o segredo, o provider do `tymon/jwt-auth` recebe `null` e explode antes
do controller: todo endpoint devolve `{"message":"Server Error"}`, inclusive o
`/api/healthcheck`, que trata as próprias exceções e por isso parecia inocente.
Rota inexistente continua devolvendo 404 limpo — é o teste que separa "app não
sobe" de "middleware quebrado".

O `FRONT_API_SECRET` custa uma sessão inteira se faltar, e o sintoma aponta para
o lugar errado. Todo JWT nasce com um hash do User-Agent de quem o pediu
(`User::getJWTCustomClaims`), e o `AuthenticateJWT` recusa quem chegar com outro.
No reload de qualquer página quem busca `/user` é o **SSR do Nuxt**, com o agente
do Nitro — não o do browser. Sem o segredo casando com o `NUXT_API_SECRET` do
`client`, essa requisição é lida como token roubado e volta 401. O usuário navega
sem problema e é deslogado no primeiro F5. O código também repassa o User-Agent
do browser no SSR, então as duas camadas se cobrem — mas com as duas de pé o
`x-api-secret` resolve antes, sem depender de header nenhum.

`JWT_TTL` decide quanto tempo a sessão dura: o `Max-Age` do cookie `opnform_token`
é copiado dele (`MentorfySsoController` devolve `expires_in`, o client grava). Não
existe refresh deslizante, então baixar esse valor é logout duro no fim do prazo,
inclusive para quem entrou pelo SSO da Mentorfy. `JWT_REFRESH_TTL` só precisa
ficar acima do `JWT_TTL`; o default do pacote é 14 dias, menor que os 30 daqui.

`LOG_CHANNEL=errorlog` não é preferência. Em php-fpm o `stderr` do worker é
descartado por padrão, então `LOG_CHANNEL=stderr` não produz **nada**: a
requisição volta 500 e o log do container fica limpo. O canal `errorlog` escreve
no error_log do master, que a imagem oficial já aponta para o stderr do PID 1.
O `docker/php-fpm-entrypoint` também liga `catch_workers_output`, que cobre erro
fatal de PHP — o que acontece antes do Laravel conseguir logar qualquer coisa.

Falta ainda o bloco `MAIL_*`. O default de `config/mail.php` é `ses`, sem
credencial nenhuma. Cadastro funciona (o e-mail vai para a fila e falha no
worker, sem derrubar a requisição), mas verificação de e-mail e reset de senha
não saem.

### Só no `api`

```bash
PHP_FPM_LISTEN=[::]:9000
```

A imagem base escuta em IPv4. A rede privada do Railway
[recomenda escutar em `::`](https://docs.railway.com/private-networking) para
funcionar também em ambientes IPv6-only. Adicionei suporte a essa variável no
`docker/php-fpm-entrypoint` — sem ela, o ingress não alcança a API.

### Só no `client`

```bash
HOST=::
PORT=3000
NUXT_PUBLIC_API_BASE=https://forms.mentorfy.io/api
NUXT_PUBLIC_APP_URL=https://forms.mentorfy.io
NUXT_PRIVATE_API_BASE=http://opnform-ingress.railway.internal:8080/api
NUXT_PUBLIC_ENV=production
NUXT_API_SECRET=                    # mesmo valor de FRONT_API_SECRET na api
```

O `NUXT_API_SECRET` e o `FRONT_API_SECRET` são um par: setar só um lado não
adianta nada, porque o header sai e não casa. Sobem juntos ou não sobem.

O `runtimeConfig.js` lê tudo em **runtime**, não no build — então não precisa de
build args, e mudar uma dessas variáveis não exige rebuild da imagem.

O `NUXT_PRIVATE_API_BASE` aponta para o **ingress**, não para a `api`. A porta
9000 fala FastCGI, não HTTP — mandar o SSR para lá quebra toda requisição
server-side. O caminho tem que incluir `/api`, senão o nginx casa o `location /`
e devolve a requisição para o próprio client.

Deixar essas duas variáveis vazias não degrada para "sem API": degrada para
**recursão**. Com `baseURL` vazio, o `$fetch` do servidor vira URL relativa,
volta para o próprio Nitro, cai no catch-all de SSR, renderiza a página, que
dispara o plugin `feature-flags.server.js` de novo. O processo chega a 4 GB em
uns 3 minutos e morre de OOM, sem uma única requisição no log.

`HOST=::` pelo mesmo motivo do php-fpm: o Nitro escuta em IPv4 por padrão.

### Só no `ingress`

```bash
PORT=8080
OPNFORM_API_HOST=opnform.railway.internal:9000
OPNFORM_CLIENT_HOST=opnform-cliente.railway.internal:3000
NGINX_MAX_BODY_SIZE=64m
```

Esses dois hosts vêm dos nomes reais dos serviços — confira o
`RAILWAY_PRIVATE_DOMAIN` de cada um antes de copiar.

## Storage: vá de S3 desde o começo

No Compose, `api`, `worker` e `scheduler` montam o mesmo volume
`opnform_storage`. No Railway isso não é possível: [cada serviço tem no máximo um
volume, e volume não se anexa a dois serviços](https://docs.railway.com/volumes/reference).

Arquivo que a `api` gravar em disco o `worker` não enxerga — e é o worker que
monta o payload do webhook. O resultado seria upload de aluno chegando quebrado
na Mentorfy, de forma intermitente e difícil de diagnosticar.

```bash
FILESYSTEM_DISK=s3
FILESYSTEM_CLOUD=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=...
```

Isso também resolve o item de "URLs assinadas expiram" que ficou aberto na
análise, e libera escalar a `api` para mais de uma réplica depois.

## Ordem de subida

1. Plugins **PostgreSQL** e **Redis**
2. Serviço `api` — sozinho, sem domínio
3. Serviços `worker` e `scheduler`
4. Serviço `client`
5. Serviço `ingress` — e só aqui gere o domínio

O `docker/php-fpm-entrypoint` roda `migrate --force` sozinho no papel `api`
(worker e scheduler pulam). Não precisa rodar migration à mão.

Suba a `api` isolada antes do resto: ela tem um `wait_for_db` em laço infinito.
Se as variáveis do banco estiverem erradas, o log fica repetindo "Waiting for DB
to be ready" — que é informação clara, mas some no meio do ruído se os cinco
serviços subirem juntos.

## Depois que tudo estiver no ar

1. `https://forms.mentorfy.io/api/healthcheck` responde
2. Crie o usuário de `MENTORFY_SERVICE_ACCOUNT_EMAIL` e gere o PAT
   (`opnform-secrets/README.md` tem os escopos exatos)
3. Preencha `OPNFORM_SERVICE_TOKEN` no `mentorfy-backend`
4. Confirme que **dois** usuários podem existir sem erro de assentos — é a prova
   de que o pin em `fff65c20` fez o que devia
