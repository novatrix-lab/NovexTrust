<?php

declare(strict_types=1);

namespace App\Notifications\Whatsapp;

use Twilio\Rest\Client;

/**
 * Sends WhatsApp messages via Twilio.
 *
 * Two modes (WhatsApp Business rules — Meta requires an approved template for
 * business-initiated messages outside a 24h session):
 *  - If a Content template SID is configured, send via that template, passing the
 *    composed message as template variable {{1}} (create a single-variable
 *    "utility" template in Twilio). This is the production path.
 *  - Otherwise send the body as free text (Twilio Sandbox / in-session).
 */
final class TwilioWhatsappSender implements WhatsappSender
{
    public function __construct(
        private readonly Client $client,
        private readonly string $from,
        private readonly ?string $contentSid = null,
    ) {}

    public function send(string $toPhone, string $body): void
    {
        $options = ['from' => $this->whatsapp($this->from)];

        if (!blank($this->contentSid)) {
            $options['contentSid'] = $this->contentSid;
            // WhatsApp template parameters may not contain newlines (or runs of
            // 4+ spaces), so flatten the composed message to a single line.
            $options['contentVariables'] = json_encode(['1' => $this->flatten($body)], JSON_THROW_ON_ERROR);
        } else {
            $options['body'] = $body;
        }

        $this->client->messages->create($this->whatsapp($toPhone), $options);
    }

    private function flatten(string $text): string
    {
        $text = (string) preg_replace('/[\r\n]+/', ' - ', $text);

        return trim((string) preg_replace('/ {2,}/', ' ', $text));
    }

    private function whatsapp(string $number): string
    {
        return str_starts_with($number, 'whatsapp:') ? $number : 'whatsapp:'.$number;
    }
}
