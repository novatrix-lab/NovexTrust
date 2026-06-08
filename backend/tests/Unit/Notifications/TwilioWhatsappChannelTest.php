<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Notifications\AlertMessage;
use App\Notifications\Channels\TwilioWhatsappChannel;
use App\Notifications\Whatsapp\WhatsappSender;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TwilioWhatsappChannelTest extends TestCase
{
    private function recordingSender(): WhatsappSender
    {
        return new class implements WhatsappSender
        {
            /** @var list<array{to: string, body: string}> */
            public array $sent = [];

            public function send(string $toPhone, string $body): void
            {
                $this->sent[] = ['to' => $toPhone, 'body' => $body];
            }
        };
    }

    private function message(?string $phone): AlertMessage
    {
        return new AlertMessage('Subject', 'Trade Licence is due soon.', 'Owner', 'o@x.test', $phone, 'en');
    }

    public function test_sends_the_body_to_the_recipient_phone(): void
    {
        $sender = $this->recordingSender();

        (new TwilioWhatsappChannel($sender))->send($this->message('+971500000000'));

        $this->assertCount(1, $sender->sent);
        $this->assertSame('+971500000000', $sender->sent[0]['to']);
        $this->assertStringContainsString('Trade Licence', $sender->sent[0]['body']);
    }

    public function test_throws_when_recipient_has_no_phone(): void
    {
        $this->expectException(RuntimeException::class);
        (new TwilioWhatsappChannel($this->recordingSender()))->send($this->message(null));
    }
}
