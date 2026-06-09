<?php

declare(strict_types=1);

namespace App\Notifications\Whatsapp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends WhatsApp messages via the Meta WhatsApp Cloud API (direct — no BSP).
 *
 * If a template name is configured, sends an approved template with the composed
 * message as body variable {{1}} (flattened — WhatsApp template params may not
 * contain newlines). Otherwise sends free text (works for the Cloud API test
 * number and within a 24h customer-service window).
 */
final class MetaCloudWhatsappSender implements WhatsappSender
{
    public function __construct(
        private readonly string $token,
        private readonly string $phoneNumberId,
        private readonly ?string $templateName = null,
        private readonly string $language = 'en',
        private readonly string $apiVersion = 'v21.0',
    ) {}

    public function send(string $toPhone, string $body): void
    {
        $to = ltrim($toPhone, '+');

        $payload = !blank($this->templateName)
            ? [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $this->templateName,
                    'language' => ['code' => $this->language],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => $this->flatten($body)]],
                    ]],
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $body],
            ];

        $response = Http::withToken($this->token)
            ->asJson()
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages", $payload);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp (Meta Cloud API) send failed: '.$response->body());
        }
    }

    private function flatten(string $text): string
    {
        $text = (string) preg_replace('/[\r\n]+/', ' - ', $text);

        return trim((string) preg_replace('/ {2,}/', ' ', $text));
    }
}
