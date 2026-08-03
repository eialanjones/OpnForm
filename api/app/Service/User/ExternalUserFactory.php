<?php

namespace App\Service\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cria usuários originados de um provedor externo (OAuth, SSO).
 *
 * Reimplementação em território AGPL da fábrica que o upstream mantinha em
 * `api/app/Enterprise/` — diretório sob licença Enterprise, removido deste fork.
 * A classe original não tinha nada de específico de OIDC: era criação de
 * usuário com e-mail já verificado. Esta versão cumpre o mesmo contrato público
 * consumido por `OAuthUserService`.
 */
class ExternalUserFactory
{
    public function createVerifiedExternalUser(
        string $name,
        string $email,
        string $provider,
        ?string $providerUserId = null,
        mixed $utmData = null,
        array $extraMeta = [],
        bool $setRandomPassword = false,
    ): User {
        $attributes = [
            'name' => $name,
            'email' => strtolower($email),
            'utm_data' => is_string($utmData) ? json_decode($utmData, true) : $utmData,
            'meta' => array_merge([
                'signup_provider' => $provider,
                'signup_provider_user_id' => $providerUserId,
                'registration_ip' => app()->bound('request') ? request()->ip() : null,
            ], $extraMeta),
        ];

        if ($setRandomPassword) {
            // Senha aleatória impronunciável: a conta existe para autenticar por
            // provedor externo, então login por formulário fica inviável na
            // prática mesmo que a rota siga exposta.
            $attributes['password'] = Hash::make(Str::random(64));
        }

        // forceFill porque email_verified_at não está em $fillable.
        $user = new User();
        $user->forceFill($attributes);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
