<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Service\Mentorfy\MentorfyProvisioner;
use App\Service\Mentorfy\MentorfyTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Troca o token de SSO da Mentorfy por um JWT do OpnForm.
 *
 * O client Nuxt guarda o JWT no cookie `opnform_token` — mesmo mecanismo do
 * login por formulário, então tudo que já existe (guards, middleware, refresh)
 * continua valendo sem adaptação.
 *
 * Fluxo completo:
 *
 *   1. Mentor clica em "Formulários" na Mentorfy
 *   2. Front chama POST /opnform/sso na API da Mentorfy (sessão normal)
 *   3. Mentorfy assina RS256 (60s, uso único) e devolve a URL
 *   4. Browser navega para /auth/mentorfy?token=… (página Nuxt do OpnForm)
 *   5. A página chama ESTE endpoint
 *   6. Verificação → provisionamento → JWT do OpnForm
 *   7. Página grava o cookie e redireciona para a UI
 */
class MentorfySsoController extends Controller
{
    public function __construct(
        private readonly MentorfyTokenVerifier $verifier,
        private readonly MentorfyProvisioner $provisioner,
    ) {
    }

    public function exchange(Request $request): JsonResponse
    {
        if (!config('mentorfy.sso_enabled')) {
            return response()->json(['message' => 'SSO não habilitado.'], 404);
        }

        $request->validate([
            'token' => 'required|string|max:4096',
        ]);

        try {
            $identity = $this->verifier->verify($request->input('token'));
        } catch (Throwable $e) {
            // Mensagem genérica para fora, detalhe no log: a razão exata da
            // recusa é informação útil para quem estiver sondando.
            Log::warning('[mentorfy-sso] token recusado', ['reason' => $e->getMessage()]);

            return response()->json(['message' => 'Não foi possível validar a sessão.'], 401);
        }

        try {
            $user = $this->provisioner->provision($identity);
        } catch (Throwable $e) {
            Log::error('[mentorfy-sso] provisionamento falhou', [
                'profile_id' => $identity['sub'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Não foi possível preparar sua conta.'], 500);
        }

        if ($user->blocked_at !== null) {
            return response()->json(['message' => 'Conta bloqueada.'], 403);
        }

        $guard = auth('api');
        $token = $guard->login($user);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ]);
    }
}
