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
 * Sends Laravel mail over Resend's HTTPS API without requiring the resend-php SDK.
 */
class ResendHttpTransport extends AbstractTransport
{
    public function __construct(protected string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('RESEND_API_KEY is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = array_filter([
            'from' => $envelope->getSender()->toString(),
            'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
            'cc' => $this->stringifyAddresses($email->getCc()),
            'bcc' => $this->stringifyAddresses($email->getBcc()),
            'reply_to' => $this->stringifyAddresses($email->getReplyTo()),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
        ], static fn ($value) => $value !== null && $value !== []);

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(15)
            ->post('https://api.resend.com/emails', $payload);

        if ($response->failed()) {
            $errorMessage = $response->json('message') ?? $response->body();
            throw new TransportException(
                sprintf('Resend API request failed (%d): %s', $response->status(), $errorMessage),
                $response->status()
            );
        }

        $messageId = $response->json('id');
        if (is_string($messageId) && $messageId !== '') {
            $email->getHeaders()->addHeader('X-Resend-Email-ID', $messageId);
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

    public function __toString(): string
    {
        return 'resend';
    }
}
