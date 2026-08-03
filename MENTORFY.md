# Fork Mentorfy do OpnForm

Base fixada em **`fff65c20`** (27/05/2026) — o último commit antes do
"OpnForm V2 🚀 (#1053)", que introduziu num só golpe o `SelfHostedSeatLimitService`
(limite de 2 usuários), o `LicenseService` e o `config/plans.php`.

Nada de lógica de licenciamento foi modificado neste fork. Não precisou: essa
camada simplesmente não existe nesta versão. Confirme a qualquer momento:

```bash
ls api/app/Service/License/   # não existe
ls api/config/plans.php       # não existe
```

Com `SELF_HOSTED=true` e `CASHIER_KEY` vazio, `pricing_enabled()` é falso, e
`User::getIsSubscribedAttribute()` e `Workspace::getIsProAttribute()` retornam
`true` para todos. Usuários, workspaces e formulários ilimitados.

## O que mudou em relação ao upstream

**Removido — `api/app/Enterprise/`.** Diretório sob licença Enterprise, que
proíbe uso em produção sem contrato. Saiu inteiro, junto com os controllers de
OIDC, factories, testes e `config/oidc.php`.

**Reescrito — `App\Service\User\ExternalUserFactory`.** O upstream mantinha essa
fábrica dentro do diretório Enterprise, embora ela não tenha nada de específico
de OIDC: é criação de usuário com e-mail verificado, usada pelo fluxo de OAuth do
Google. Reimplementada aqui em território AGPL, mesmo contrato público.

**Adicionado — SSO da Mentorfy.**

| Arquivo | Papel |
|---|---|
| `api/app/Service/Mentorfy/MentorfyTokenVerifier.php` | Verificação RS256 sobre openssl nativo, sem dependência nova |
| `api/app/Service/Mentorfy/MentorfyProvisioner.php` | Cria usuário e workspace, anexa a conta de serviço, avisa a Mentorfy |
| `api/app/Http/Controllers/Auth/MentorfySsoController.php` | Troca o token curto por JWT do OpnForm |
| `api/config/mentorfy.php` | Configuração |
| `api/database/migrations/2026_08_03_000000_add_mentorfy_sso_columns.php` | `users.mentorfy_profile_id`, `workspaces.mentorfy_owner_profile_id` |
| `client/pages/auth/mentorfy.vue` | Página que fecha a sessão no browser |

Rota registrada em `api/routes/api.php`, no grupo público:
`POST /auth/mentorfy/exchange`, com `throttle:20,1`.

## Ambiente

```bash
SELF_HOSTED=true
CASHIER_KEY=
APP_URL=https://forms.mentorfy.io

MENTORFY_SSO_ENABLED=true
MENTORFY_SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n…"
MENTORFY_SSO_ISSUER=mentorfy
MENTORFY_SSO_AUDIENCE=opnform
MENTORFY_SERVICE_ACCOUNT_EMAIL=gestao@mentorfy.io
MENTORFY_PROVISIONED_CALLBACK_URL=https://api.mentorfy.io/api/v1/opnform/hooks/provisioned
MENTORFY_API_KEY=<mesmo valor de OPNFORM_WEBHOOK_API_KEY na Mentorfy>
MENTORFY_WORKSPACE_NAME=Meus formulários
```

Só a chave **pública** entra aqui. Esta instância precisa ser incapaz de emitir
identidade válida para a Mentorfy mesmo se for inteiramente comprometida — é o
ponto central do desenho, e é o que justifica RS256 em vez de segredo
compartilhado.

## Subir

```bash
docker compose up -d          # api, api-worker, api-scheduler, redis, postgres, nginx
docker compose exec api php artisan migrate
```

O `api-worker` é obrigatório: é ele que dispara os webhooks de submissão.

Depois, crie o usuário da conta de serviço com o e-mail de
`MENTORFY_SERVICE_ACCOUNT_EMAIL` e gere um PAT em **Settings → Tokens** com os
escopos `workspaces-read`, `forms-read`, `forms-write`, `manage-integrations`.
Esse token é o `OPNFORM_SERVICE_TOKEN` do lado da Mentorfy.

## Resíduos conhecidos de OIDC no client

O código Vue de OIDC (`client/api/oidc.js`, `client/composables/useOidcLinking.js`,
`client/lib/oidc/`, `client/components/workspaces/settings/sso/`) continua no
repositório. É AGPL, então não há problema de licença — mas está inerte:

- A aba **SSO** foi removida do `settings/Modal.vue`, então mentores não a veem
- A página de callback `auth/[slug]/callback.vue` foi removida
- O `LoginForm` lê `useFeatureFlag('oidc.available', false)`; como a flag saiu do
  `FeatureFlagsController`, ele degrada para login por senha normal
- `completeLinkIfNeeded()` retorna cedo quando não há `linkToken`

Não removi os componentes restantes porque não consigo rodar o build do Nuxt
daqui para validar. Se quiserem limpar, é seguro — só rodem o build depois.

## Manutenção

A partir daqui, patches de segurança são de vocês. O remote `origin` aponta para
o upstream; para trazer só correções de segurança:

```bash
git fetch --unshallow origin          # uma vez, se precisar de histórico
git log --oneline fff65c20..origin/main -- api/app | grep -i "security\|CVE\|fix"
git cherry-pick <sha>
```

## AGPL §13

Este fork é modificado e servido pela rede. A licença exige oferecer o
código-fonte modificado a quem interage remotamente — mentores e alunos
inclusive. Publiquem este repositório e coloquem o link no rodapé da aplicação e
das páginas públicas de formulário.

A AGPL não concede direito de marca: troquem nome, logo e favicon antes de expor
a instância.
