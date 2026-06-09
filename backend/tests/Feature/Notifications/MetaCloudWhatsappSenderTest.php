<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Notifications\Whatsapp\MetaCloudWhatsappSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MetaCloudWhatsappSenderTest extends TestCase
{
    public function test_sends_a_template_message_to_the_graph_api(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        (new MetaCloudWhatsappSender('TOKEN', '123456', 'novex_reminder', 'en'))
            ->send('+971500000000', "Hello\n\nLicence due");

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/v21.0/123456/messages')
                && $request->hasHeader('Authorization', 'Bearer TOKEN')
                && $request['to'] === '971500000000'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'novex_reminder'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Hello - Licence due';
        });
    }

    public function test_sends_free_text_when_no_template_configured(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.Y']]], 200)]);

        (new MetaCloudWhatsappSender('TOKEN', '123456'))->send('+971500000000', 'Plain hello');

        Http::assertSent(fn ($request): bool => $request['type'] === 'text' && $request['text']['body'] === 'Plain hello');
    }

    public function test_throws_on_api_error(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'bad token']], 401)]);

        $this->expectException(RuntimeException::class);
        (new MetaCloudWhatsappSender('TOKEN', '123456'))->send('+971500000000', 'hi');
    }
}
