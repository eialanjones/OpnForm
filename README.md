# Forms Mentorfy

<p align="center">
<img src="client/public/img/social-preview.jpg">
</p>

Forms Mentorfy is the form builder used by [Mentorfy](https://www.mentorfy.com.br),
self-hosted at `forms.mentorfy.com.br`.

It is a fork of [OpnForm](https://github.com/OpnForm/OpnForm), pinned to the last
commit before OpnForm V2 and stripped of the Enterprise-licensed directory.
[MENTORFY.md](MENTORFY.md) records exactly what diverges from upstream: the
Mentorfy SSO flow, the removed Enterprise code, and the required environment.

## Key Features

-   🚀 No-code builder with unlimited forms & submissions
-   📝 Various input types: Text, Date, URL, File uploads & much more
-   🌐 Embed anywhere
-   📧 Email notifications
-   💬 Integrations (Slack, Webhooks, Discord)
-   🧠 Form logic & customization
-   🛡️ Captcha protection
-   📊 Form analytics
-   🔑 Single sign-on from the Mentorfy platform

## Running it

Deployment on Railway — the environment this runs in — is documented in
[RAILWAY.md](RAILWAY.md). For local development the repository ships a Docker
Compose setup:

```bash
./scripts/docker-setup.sh
```

Upstream's [technical documentation](https://docs.opnform.com) still applies to
everything this fork did not change, which is most of the product.

## License

This project is **open-source** under the GNU Affero General Public License
Version 3 (AGPLv3) or any later version — see [LICENSE](LICENSE).

Copyright on the upstream codebase belongs to the OpnForm authors and that notice
is left intact. Modifications in this fork are © Mentorfy Educação LTDA, released
under the same license.

Upstream OpnForm also ships an Enterprise Edition under a separate proprietary
license. That directory (`api/app/Enterprise/`) is **not** part of this fork — it
was removed — so nothing here is covered by that license.
