<?php

declare(strict_types=1);

namespace App\Providers;

use App\Extraction\Extractor;
use App\Extraction\StubExtractor;
use App\Notifications\Channels\ApiWhatsappChannel;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\WhatsappChannel;
use App\Notifications\NotificationChannelRegistry;
use App\Notifications\Whatsapp\MetaCloudWhatsappSender;
use App\Notifications\Whatsapp\TwilioWhatsappSender;
use App\Notifications\Whatsapp\WhatsappSender;
use App\Tenancy\TenantContext;
use App\Vault\DocumentVault;
use App\Vault\Kms\KeyManagementService;
use App\Vault\Kms\LocalKms;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Twilio\Rest\Client as TwilioClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One tenant context per request lifecycle; the enforcement point for
        // strict per-tenant isolation (see TenantScope / BelongsToTenant).
        $this->app->singleton(TenantContext::class);

        // KMS (key custody, separate from data) — swappable per config.
        $this->app->singleton(KeyManagementService::class, function (): KeyManagementService {
            return match (config('vault.driver')) {
                'local' => new LocalKms(config('vault.master_key'), config('vault.kek_id')),
                default => throw new RuntimeException('Unsupported vault KMS driver: '.config('vault.driver')),
            };
        });

        // The encrypted document vault, over the configured storage disk.
        $this->app->singleton(DocumentVault::class, function ($app): DocumentVault {
            return new DocumentVault(
                $app->make(KeyManagementService::class),
                Storage::disk(config('vault.disk')),
            );
        });

        // Document-AI extractor (behind an interface; stub until a vendor is set).
        $this->app->singleton(Extractor::class, function (): Extractor {
            return match (config('extraction.driver')) {
                'stub' => new StubExtractor,
                default => throw new RuntimeException('Unsupported extraction driver: '.config('extraction.driver')),
            };
        });

        // WhatsApp transport: Meta Cloud API or Twilio, per WHATSAPP_PROVIDER.
        $this->app->bind(WhatsappSender::class, function (): WhatsappSender {
            return match (config('services.whatsapp.provider')) {
                'meta' => new MetaCloudWhatsappSender(
                    (string) config('services.meta_whatsapp.token'),
                    (string) config('services.meta_whatsapp.phone_id'),
                    config('services.meta_whatsapp.template'),
                    (string) config('services.meta_whatsapp.language', 'en'),
                ),
                default => new TwilioWhatsappSender(
                    new TwilioClient(config('services.twilio.sid'), config('services.twilio.token')),
                    (string) config('services.twilio.whatsapp_from'),
                    config('services.twilio.whatsapp_content_sid'),
                ),
            };
        });

        // Notification channels (email real; WhatsApp real via the configured
        // provider, else the no-op stub; push is a future drop-in).
        $this->app->singleton(NotificationChannelRegistry::class, function ($app): NotificationChannelRegistry {
            $registry = new NotificationChannelRegistry;
            $registry->register($app->make(EmailChannel::class));

            $registry->register(
                $this->whatsappConfigured()
                    ? $app->make(ApiWhatsappChannel::class)
                    : $app->make(WhatsappChannel::class),
            );

            return $registry;
        });
    }

    private function whatsappConfigured(): bool
    {
        return config('services.whatsapp.provider') === 'meta'
            ? filled(config('services.meta_whatsapp.token')) && filled(config('services.meta_whatsapp.phone_id'))
            : filled(config('services.twilio.sid')) && filled(config('services.twilio.whatsapp_from'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
