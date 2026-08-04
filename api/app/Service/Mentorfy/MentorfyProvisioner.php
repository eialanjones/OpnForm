<?php

namespace App\Service\Mentorfy;

use App\Models\User;
use App\Models\UserWorkspace;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cria (ou recupera) o usuário e o workspace do mentor a partir do token de SSO.
 *
 * Duas decisões que valem explicar:
 *
 * WORKSPACE POR DONO, NÃO POR USUÁRIO. O workspace pertence ao mentor dono e é
 * localizado por `mentorfy_owner_profile_id`. Assim um membro de time pode
 * entrar antes do próprio mentor sem criar workspace paralelo — e sem que a
 * ordem de login mude o resultado.
 *
 * CONTA DE SERVIÇO COMO ADMIN. A Mentorfy precisa enxergar formulários e
 * submissões de todos os mentores usando um único PAT. Um token por mentor
 * exigiria login programático de cada um. O preço é a conta de serviço aparecer
 * na lista de membros do workspace; o ganho é um mecanismo só para descoberta,
 * anexo de webhook e reconciliação.
 */
class MentorfyProvisioner
{
    /**
     * @param array{sub:string,email:string,name:string,role:string,workspace_owner:string} $identity
     */
    public function provision(array $identity): User
    {
        [$user, $workspace] = DB::transaction(function () use ($identity) {
            $user = $this->resolveUser($identity);
            $workspace = $this->resolveWorkspace($identity['workspace_owner']);

            $this->attach($user, $workspace, $this->normalizeRole($identity['role']));
            $this->attachServiceAccount($workspace);

            return [$user, $workspace];
        });

        // Fora da transação de propósito. É uma chamada HTTP com timeout de 5s no
        // caminho do login: dentro da transação ela seguraria os locks do
        // provisionamento por todo esse tempo. Depois do commit também é mais
        // honesto — a Mentorfy só ouve falar de um vínculo que de fato existe.
        $this->notifyMentorfy($identity['workspace_owner'], $workspace, $identity);

        return $user;
    }

    private function resolveUser(array $identity): User
    {
        $existing = User::where('mentorfy_profile_id', $identity['sub'])->first();

        if ($existing) {
            // Nome e e-mail seguem a Mentorfy como fonte da verdade.
            $existing->fill([
                'name' => $identity['name'],
                'email' => $identity['email'],
            ])->save();

            return $existing;
        }

        // A tabela users tem UNIQUE em email. Se já existe alguém com este
        // e-mail sem vínculo, adotamos — é o caso do usuário criado à mão antes
        // do SSO entrar no ar. Se já está vinculado a OUTRO profile, é conflito
        // real e precisa de decisão humana.
        $byEmail = User::where('email', $identity['email'])->first();

        if ($byEmail) {
            if (!empty($byEmail->mentorfy_profile_id)) {
                throw new RuntimeException(
                    "E-mail {$identity['email']} já vinculado ao profile {$byEmail->mentorfy_profile_id}."
                );
            }

            Log::info('[mentorfy-sso] adotando usuário existente por e-mail', [
                'user_id' => $byEmail->id,
                'profile_id' => $identity['sub'],
            ]);

            $byEmail->mentorfy_profile_id = $identity['sub'];
            $byEmail->name = $identity['name'];
            $byEmail->save();

            return $byEmail;
        }

        $user = User::create([
            'name' => $identity['name'],
            'email' => $identity['email'],
            // Sem senha: a autenticação é sempre pela Mentorfy. Deixar nulo
            // impede login por formulário mesmo se a rota ficar exposta.
            'password' => null,
        ]);

        $user->mentorfy_profile_id = $identity['sub'];
        $user->email_verified_at = now();
        $user->save();

        Log::info('[mentorfy-sso] usuário criado', [
            'user_id' => $user->id,
            'profile_id' => $identity['sub'],
        ]);

        return $user;
    }

    private function resolveWorkspace(string $ownerProfileId): Workspace
    {
        $workspace = Workspace::where('mentorfy_owner_profile_id', $ownerProfileId)->first();

        if ($workspace) {
            return $workspace;
        }

        $workspace = Workspace::create([
            'name' => config('mentorfy.workspace_name', 'Meus formulários'),
            'icon' => '',
        ]);

        $workspace->mentorfy_owner_profile_id = $ownerProfileId;
        $workspace->save();

        Log::info('[mentorfy-sso] workspace criado', [
            'workspace_id' => $workspace->id,
            'owner_profile_id' => $ownerProfileId,
        ]);

        return $workspace;
    }

    private function attach(User $user, Workspace $workspace, string $role): void
    {
        $pivot = UserWorkspace::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->first();

        if ($pivot) {
            if ($pivot->role !== $role) {
                $pivot->update(['role' => $role]);
            }

            return;
        }

        UserWorkspace::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => $role,
        ]);
    }

    private function attachServiceAccount(Workspace $workspace): void
    {
        $email = config('mentorfy.service_account_email');
        if (empty($email)) {
            return;
        }

        $service = User::where('email', $email)->first();

        if (!$service) {
            // Não é fatal no fluxo de login do mentor, mas quebra descoberta de
            // formulários e reconciliação. Precisa de alarme.
            Log::error('[mentorfy-sso] conta de serviço não encontrada', ['email' => $email]);

            return;
        }

        $this->attach($service, $workspace, User::ROLE_ADMIN);
    }

    /**
     * Informa a Mentorfy do vínculo workspace ↔ mentor. Sem isto a descoberta
     * de formulários não sabe rotear.
     *
     * Falha aqui não derruba o login: o mentor entra, e a Mentorfy reconcilia o
     * vínculo no próximo acesso. O que não pode é o mentor ver erro de login por
     * causa de um callback administrativo.
     */
    private function notifyMentorfy(string $ownerProfileId, Workspace $workspace, array $identity): void
    {
        $url = config('mentorfy.provisioned_callback_url');
        $apiKey = config('mentorfy.api_key');

        if (empty($url) || empty($apiKey)) {
            return;
        }

        try {
            // Dentro do try junto com a chamada HTTP: fora dele, uma falha de
            // banco aqui escaparia e viraria "não foi possível preparar sua
            // conta" para um mentor cuja conta já está criada e commitada.
            $ownerUser = $ownerProfileId === $identity['sub']
                ? User::where('mentorfy_profile_id', $ownerProfileId)->first()
                : User::where('mentorfy_profile_id', $ownerProfileId)->first();

            $response = Http::timeout(5)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->post($url, array_filter([
                    'profile_id' => $ownerProfileId,
                    'opnform_user_id' => $ownerUser?->id,
                    'opnform_workspace_id' => $workspace->id,
                ], fn ($value) => $value !== null));

            if (!$response->successful()) {
                Log::warning('[mentorfy-sso] callback de provisionamento rejeitado', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[mentorfy-sso] callback de provisionamento falhou', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'admin' => User::ROLE_ADMIN,
            'readonly' => User::ROLE_READONLY,
            default => User::ROLE_USER,
        };
    }
}
