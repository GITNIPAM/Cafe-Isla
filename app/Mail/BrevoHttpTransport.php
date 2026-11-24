<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends Laravel mail over Brevo's HTTPS transactional API.
 */
class BrevoHttpTransport extends AbstractTransport
{
    public function __construct(protected string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('BREVO_API_KEY is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $sender = $envelope->getSender();

        $payload = array_filter([
            'sender' => $this->formatAddress($sender),
            'to' => $this->formatAddresses($this->getRecipients($email, $envelope)),
            'cc' => $this->formatAddresses($email->getCc()),
            'bcc' => $this->formatAddresses($email->getBcc()),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getTextBody(),
        ], static fn ($value) => $value !== null && $value !== []);

        $replyTo = $email->getReplyTo();
        if ($replyTo !== []) {
            $payload['replyTo'] = $this->formatAddress($replyTo[0]);
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
        ])
            ->acceptJson()
            ->timeout(15)
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            $errorMessage = $response->json('message') ?? $response->body();
            throw new TransportException(
                sprintf('Brevo API request failed (%d): %s', $response->status(), $errorMessage),
                $response->status()
            );
        }

        $messageId = $response->json('messageId');
        if (is_string($messageId) && $messageId !== '') {
            $email->getHeaders()->addHeader('X-Brevo-Message-ID', $messageId);
        }
    }

    /**
     * @return array<int, Address>
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        $excluded = array_merge($email->getCc(), $email->getBcc());

        return array_values(array_filter(
            $envelope->getRecipients(),
            static fn (Address $address) => ! in_array($address, $excluded, true)
        ));
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name?: string}>
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_values(array_map(
            fn (Address $address) => $this->formatAddress($address),
            $addresses
        ));
    }

    /**
     * @return array{email: string, name?: string}
     */
    protected function formatAddress(Address $address): array
    {
        $formatted = ['email' => $address->getAddress()];

        if ($address->getName() !== '') {
            $formatted['name'] = $address->getName();
        }

        return $formatted;
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
