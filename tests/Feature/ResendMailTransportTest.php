<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\DualChannelMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendMailTransportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_resend_transport_sends_email_over_https_and_logs_status(): void
    {
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 're_msg_123'], 200),
        ]);

        Config::set('mail.default', 'resend');
        Config::set('services.resend.key', 're_test_key_123');
        Config::set('mail.from.address', 'onboarding@resend.dev');
        Mail::purge('resend');

        $order = Order::with(['items', 'delivery', 'payment'])->firstOrFail();
        $log = app(DualChannelMailService::class)->sendOrderConfirmation($order);

        $this->assertSame('resend-sent + logged', $log->smtp_status);

        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://api.resend.com/emails'
                && $request->hasHeader('Authorization', 'Bearer re_test_key_123')
                && $request['to'] === [$order->billing_email]
                && str_contains($request['from'], 'onboarding@resend.dev')
                && str_contains($request['subject'], $order->order_number)
                && is_string($request['html']);
        });
    }

    public function test_resend_transport_falls_back_gracefully_when_api_key_is_missing(): void
    {
        Http::fake();

        Config::set('mail.default', 'resend');
        Config::set('services.resend.key', '');
        Mail::purge('resend');

        $order = Order::with(['items', 'delivery', 'payment'])->firstOrFail();
        $log = app(DualChannelMailService::class)->sendOrderConfirmation($order);

        $this->assertStringStartsWith('logged-fallback (', $log->smtp_status);
        Http::assertNothingSent();
    }
}
