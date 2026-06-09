<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Notifications\Whatsapp\TwilioWhatsappSender;
use Mockery;
use Tests\TestCase;
use Twilio\Rest\Client;

class TwilioWhatsappSenderTest extends TestCase
{
    public function test_sends_a_free_text_body_with_whatsapp_prefixes(): void
    {
        $messages = Mockery::mock();
        $messages->shouldReceive('create')->once()->withArgs(function (string $to, array $opts): bool {
            return $to === 'whatsapp:+971500000000'
                && $opts['from'] === 'whatsapp:+14155238886'
                && ($opts['body'] ?? null) === 'Hello';
        });
        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        (new TwilioWhatsappSender($client, '+14155238886'))->send('+971500000000', 'Hello');
    }

    public function test_uses_a_content_template_when_a_content_sid_is_configured(): void
    {
        $messages = Mockery::mock();
        $messages->shouldReceive('create')->once()->withArgs(function (string $to, array $opts): bool {
            return ($opts['contentSid'] ?? null) === 'HXtemplate'
                && ($opts['contentVariables'] ?? null) === '{"1":"Hello"}'
                && !isset($opts['body']);
        });
        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        (new TwilioWhatsappSender($client, 'whatsapp:+14155238886', 'HXtemplate'))->send('+971500000000', 'Hello');
    }

    public function test_flattens_a_multiline_body_for_the_template_variable(): void
    {
        $messages = Mockery::mock();
        $messages->shouldReceive('create')->once()->withArgs(function (string $to, array $opts): bool {
            return ($opts['contentVariables'] ?? null) === '{"1":"Hello there - Licence due - Why: matters"}';
        });
        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        (new TwilioWhatsappSender($client, '+1', 'HXtemplate'))
            ->send('+971500000000', "Hello there\n\nLicence due\n\nWhy: matters");
    }
}
