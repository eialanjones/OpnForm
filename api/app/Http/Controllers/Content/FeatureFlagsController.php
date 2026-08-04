<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class FeatureFlagsController extends Controller
{
    public function index()
    {
        $featureFlags = Cache::remember('feature_flags', 3600, function () {
            return [
                'self_hosted' => config('app.self_hosted', true),
                'setup_required' => config('app.self_hosted', true) && !\App\Models\User::max('id'),
                'custom_domains' => config('custom-domains.enabled', false),
                'ai_features' => !empty(config('services.openai.api_key')),
                'version' => $this->getAppVersion(),

                'billing' => [
                    'enabled' => !empty(config('cashier.key')) && !empty(config('cashier.secret')),
                    'appsumo' => !empty(config('services.appsumo.api_key')) && !empty(config('services.appsumo.api_secret')),
                    'stripe_publishable_key' => config('cashier.key'),
                ],
                'storage' => [
                    'local' => config('filesystems.default') === 'local',
                    's3' => config('filesystems.default') !== 'local',
                ],
                'services' => [
                    'unsplash' => !empty(config('services.unsplash.access_key')),
                    'google' => [
                        'fonts' => !empty(config('services.google.fonts_api_key')),
                        'auth' => !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret')),
                        'client_id' => config('services.google.client_id'),
                    ],
                    'telegram' => [
                        'bot_id' => $this->extractTelegramBotId()
                    ]
                ],
                'integrations' => [
                    'zapier' => config('services.zapier.enabled'),
                    'google_sheets' => !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret')),
                    'telegram' => !empty(config('services.telegram.bot_token')),
                ],
                'custom_code' => [
                    'enable_self_hosted' => (bool) config('opnform.custom_code.enable_self_hosted', false),
                ],
            ];
        });

        return response()->json($featureFlags);
    }

    /**
     * Get the application version from Docker environment or fallback
     */
    private function getAppVersion(): ?string
    {
        // Only return version for self-hosted installations
        if (!config('app.self_hosted', true)) {
            return null;
        }

        $version = trim((string) config('app.docker_version'));

        // O Dockerfile define `ARG APP_VERSION=unknown`, e um build que não
        // sobrescreve esse arg entrega a string literal "unknown". Ela é truthy,
        // então o `v-if="version"` do BaseSidebar — que existe justamente para
        // esconder o sufixo quando não há versão — não segurava nada, e o rodapé
        // mostrava "Forms Mentorfy vunknown".
        //
        // Placeholder não é versão. Devolver null faz o guard do front voltar a
        // funcionar como foi desenhado.
        if ($version === '' || strtolower($version) === 'unknown') {
            return null;
        }

        return $version;
    }

    /**
     * Extract bot ID from Telegram bot token
     */
    private function extractTelegramBotId(): string|false
    {
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            return false;
        }

        // Bot token format: 1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
        // Extract the bot ID (everything before the colon)
        $parts = explode(':', $botToken);

        return $parts[0] ?? false;
    }
}
