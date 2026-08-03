<?php

namespace App\Service\Mentorfy;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Verifica o token de SSO emitido pela Mentorfy.
 *
 * Implementado sobre a extensão openssl nativa do PHP, de propósito: não
 * adiciona dependência nova ao composer.json do fork. Como a base fica
 * congelada num commit, cada dependência a menos é uma superfície a menos para
 * manter.
 *
 * Defesas, na ordem em que importam:
 *
 * 1. `alg` fixado em RS256. Aceitar o alg do header é a vulnerabilidade
 *    clássica de confusão de algoritmo — um atacante mandaria `alg: none` ou
 *    `HS256` assinado com a chave pública (que é pública).
 * 2. Chave pública apenas. Esta instância não consegue emitir token válido nem
 *    se for inteiramente comprometida.
 * 3. `iss` e `aud` conferidos. Um token da Mentorfy para outro consumidor não
 *    vale aqui.
 * 4. `exp` com tolerância mínima de relógio.
 * 5. `jti` de uso único, guardado em cache até expirar. Replay do mesmo link
 *    não autentica de novo.
 */
class MentorfyTokenVerifier
{
    private const ALGORITHM = 'RS256';
    private const LEEWAY_SECONDS = 30;

    /**
     * @return array{sub:string,email:string,name:string,role:string,workspace_owner:string}
     */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Token malformado.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = $this->decodeSegment($encodedHeader, 'header');
        $payload = $this->decodeSegment($encodedPayload, 'payload');
        $signature = $this->base64UrlDecode($encodedSignature);

        if (($header['alg'] ?? null) !== self::ALGORITHM) {
            throw new RuntimeException('Algoritmo não suportado.');
        }

        $this->assertSignature("{$encodedHeader}.{$encodedPayload}", $signature);
        $this->assertClaims($payload);
        $this->assertSingleUse($payload);

        return [
            'sub' => (string) $payload['sub'],
            'email' => (string) $payload['email'],
            'name' => (string) ($payload['name'] ?? $payload['email']),
            'role' => (string) ($payload['role'] ?? 'admin'),
            'workspace_owner' => (string) ($payload['workspace_owner'] ?? $payload['sub']),
        ];
    }

    private function assertSignature(string $signingInput, string $signature): void
    {
        $publicKey = config('mentorfy.sso_public_key');
        if (empty($publicKey)) {
            throw new RuntimeException('MENTORFY_SSO_PUBLIC_KEY não configurada.');
        }

        $key = openssl_pkey_get_public($publicKey);
        if ($key === false) {
            throw new RuntimeException('MENTORFY_SSO_PUBLIC_KEY inválida.');
        }

        $result = openssl_verify($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);

        if ($result !== 1) {
            throw new RuntimeException('Assinatura inválida.');
        }
    }

    private function assertClaims(array $payload): void
    {
        $now = time();

        if (($payload['iss'] ?? null) !== config('mentorfy.sso_issuer')) {
            throw new RuntimeException('Emissor inesperado.');
        }

        $audience = $payload['aud'] ?? null;
        $expected = config('mentorfy.sso_audience');
        $audienceOk = is_array($audience)
            ? in_array($expected, $audience, true)
            : $audience === $expected;

        if (!$audienceOk) {
            throw new RuntimeException('Audiência inesperada.');
        }

        if (!isset($payload['exp']) || ($payload['exp'] + self::LEEWAY_SECONDS) < $now) {
            throw new RuntimeException('Token expirado.');
        }

        if (isset($payload['nbf']) && ($payload['nbf'] - self::LEEWAY_SECONDS) > $now) {
            throw new RuntimeException('Token ainda não é válido.');
        }

        foreach (['sub', 'email', 'jti'] as $claim) {
            if (empty($payload[$claim])) {
                throw new RuntimeException("Claim obrigatória ausente: {$claim}.");
            }
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail inválido no token.');
        }
    }

    /**
     * Marca o jti como consumido. `add()` é atômico no driver Redis — dois
     * cliques simultâneos no mesmo link não geram duas sessões.
     */
    private function assertSingleUse(array $payload): void
    {
        $ttl = max(60, (int) $payload['exp'] - time() + self::LEEWAY_SECONDS);
        $key = 'mentorfy_sso_jti:' . hash('sha256', (string) $payload['jti']);

        if (!Cache::add($key, true, $ttl)) {
            throw new RuntimeException('Token já utilizado.');
        }
    }

    private function decodeSegment(string $segment, string $label): array
    {
        $decoded = json_decode($this->base64UrlDecode($segment), true);

        if (!is_array($decoded)) {
            throw new RuntimeException("Não foi possível decodificar o {$label} do token.");
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;

        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($padded, true);
        if ($decoded === false) {
            throw new RuntimeException('Base64 inválido no token.');
        }

        return $decoded;
    }
}
