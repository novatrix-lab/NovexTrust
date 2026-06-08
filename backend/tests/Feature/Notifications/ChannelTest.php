<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Mail\AlertMail;
use App\Notifications\AlertMessage;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\WhatsappChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    private function message(?string $email): AlertMessage
    {
        return new AlertMessage('Subject', 'Body text', 'Recipient', $email, null, 'en');
    }

    public function test_email_channel_sends_the_mailable(): void
    {
        Mail::fake();

        (new EmailChannel)->send($this->message('to@test.test'));

        Mail::assertSent(AlertMail::class, fn (AlertMail $mail): bool => $mail->hasTo('to@test.test') && $mail->message->subject === 'Subject');
    }

    public function test_email_channel_throws_without_a_recipient_address(): void
    {
        Mail::fake();

        $this->expectException(RuntimeException::class);
        (new EmailChannel)->send($this->message(null));
    }

    public function test_whatsapp_channel_is_a_stub_that_logs_and_does_not_throw(): void
    {
        Log::spy();

        (new WhatsappChannel)->send($this->message(null));

        Log::shouldHaveReceived('info')->withArgs(
            fn (string $message): bool => $message === 'notifications.whatsapp.stub'
        )->once();
    }
}
