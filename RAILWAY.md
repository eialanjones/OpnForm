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
| `ingress` | `railway/ingress.json` | ✅ `forms.mentorfy.com.br` | `$PORT` |
| `api` | `railway/api.json` | ❌ | `[::]:9000` |
| `client` | `railway/client.json` | ❌ | `[::]:3000` |
| `worker` | `railway/worker.json` | ❌ | — |
| `scheduler` | `railway/scheduler.json` | ❌ | — |

Mais os plugins **PostgreSQL** e **Redis**.

Os cinco apontam para o **mesmo repositório**. O que muda é o caminho do
config-as-code (Settings → Config as Code) e as variáveis.

## Nome dos serviços importa

O `ingress` alcança os outros por `api.railway.internal:9000` e
`client.railway.internal:3000`. Se você nomear diferente, ajuste
`OPNFORM_API_HOST` e `OPNFORM_CLIENT_HOST` no serviço `ingress`.

Na rede privada do Railway a porta **não** é inferida — tem que vir explícita no
host, e é por isso que ela aparece nessas duas variáveis.

## Variáveis

### Compartilhadas por `api`, `worker` e `scheduler`

```bash
APP_ENV=production
APP_KEY=                      # php artisan key:generate --show
APP_URL=https://forms.mentorfy.com.br
SELF_HOSTED=true
CASHIER_KEY=

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
NUXT_PUBLIC_API_BASE=https://forms.mentorfy.com.br/api
NUXT_PUBLIC_APP_URL=https://forms.mentorfy.com.br
NUXT_PRIVATE_API_BASE=http://api.railway.internal:9000   # não usado hoje, mas é o par server-side
NUXT_PUBLIC_ENV=production
```

O `runtimeConfig.js` lê tudo em **runtime**, não no build — então não precisa de
build args, e mudar uma dessas variáveis não exige rebuild da imagem.

`HOST=::` pelo mesmo motivo do php-fpm: o Nitro escuta em IPv4 por padrão.

### Só no `ingress`

```bash
OPNFORM_API_HOST=api.railway.internal:9000
OPNFORM_CLIENT_HOST=client.railway.internal:3000
NGINX_MAX_BODY_SIZE=64m
```

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

1. `https://forms.mentorfy.com.br/api/healthcheck` responde
2. Crie o usuário de `MENTORFY_SERVICE_ACCOUNT_EMAIL` e gere o PAT
   (`opnform-secrets/README.md` tem os escopos exatos)
3. Preencha `OPNFORM_SERVICE_TOKEN` no `mentorfy-backend`
4. Confirme que **dois** usuários podem existir sem erro de assentos — é a prova
   de que o pin em `fff65c20` fez o que devia
