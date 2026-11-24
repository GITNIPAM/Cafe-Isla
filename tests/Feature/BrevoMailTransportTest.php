<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\DualChannelMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrevoMailTransportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_brevo_transport_sends_email_over_https_and_logs_status(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<msg_123@brevo>'], 201),
        ]);

        Config::set('mail.default', 'brevo');
        Config::set('services.brevo.key', 'xkeysib-test-123');
        Config::set('mail.from.address', 'care.cafeisla@gmail.com');
        Config::set('mail.from.name', 'Café Isla');
        Mail::purge('brevo');

        $order = Order::with(['items', 'delivery', 'payment'])->firstOrFail();
        $log = app(DualChannelMailService::class)->sendOrderConfirmation($order);

        $this->assertSame('brevo-sent + logged', $log->smtp_status);

        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'xkeysib-test-123')
                && ($request['sender']['email'] ?? null) === 'care.cafeisla@gmail.com'
                && ($request['to'][0]['email'] ?? null) === $order->billing_email
                && str_contains((string) $request['subject'], $order->order_number)
                && is_string($request['htmlContent']);
        });
    }

    public function test_brevo_transport_falls_back_gracefully_when_api_key_is_missing(): void
    {
        Http::fake();

        Config::set('mail.default', 'brevo');
        Config::set('services.brevo.key', '');
        Mail::purge('brevo');

        $order = Order::with(['items', 'delivery', 'payment'])->firstOrFail();
        $log = app(DualChannelMailService::class)->sendOrderConfirmation($order);

        $this->assertStringStartsWith('logged-fallback (', $log->smtp_status);
        Http::assertNothingSent();
    }
}
