<?php

return [
    /*
    | Liga o SSO. Desligado, a rota devolve 404 — não 403 — para não anunciar
    | que o endpoint existe numa instância que não o usa.
    */
    'sso_enabled' => (bool) env('MENTORFY_SSO_ENABLED', true),

    /*
    | Chave PÚBLICA RS256 (PEM) que valida os tokens da Mentorfy.
    |
    | Só a pública. Esta instância não deve ser capaz de emitir token válido nem
    | se for inteiramente comprometida — é o ponto central do desenho.
    |
    | Painéis de deploy costumam escapar quebras de linha; o replace abaixo
    | resolve o caso de a chave chegar com \n literal.
    */
    'sso_public_key' => str_replace('\n', "\n", (string) env('MENTORFY_SSO_PUBLIC_KEY', '')),

    'sso_issuer' => env('MENTORFY_SSO_ISSUER', 'mentorfy'),
    'sso_audience' => env('MENTORFY_SSO_AUDIENCE', 'opnform'),

    /*
    | E-mail da conta de serviço da Mentorfy nesta instância. Ela é adicionada
    | como admin de todo workspace provisionado — é o que dá ao PAT único
    | visibilidade sobre formulários e submissões de todos os mentores.
    */
    'service_account_email' => env('MENTORFY_SERVICE_ACCOUNT_EMAIL'),

    /*
    | Callback que informa a Mentorfy do vínculo workspace ↔ mentor.
    */
    'provisioned_callback_url' => env('MENTORFY_PROVISIONED_CALLBACK_URL'),
    'api_key' => env('MENTORFY_API_KEY'),

    /*
    | Nome dado ao workspace criado no primeiro acesso do mentor.
    */
    'workspace_name' => env('MENTORFY_WORKSPACE_NAME', 'Meus formulários'),
];
