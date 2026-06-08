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
            $options['contentVariables'] = json_encode(['1' => $body], JSON_THROW_ON_ERROR);
        } else {
            $options['body'] = $body;
        }

        $this->client->messages->create($this->whatsapp($toPhone), $options);
    }

    private function whatsapp(string $number): string
    {
        return str_starts_with($number, 'whatsapp:') ? $number : 'whatsapp:'.$number;
    }
}
