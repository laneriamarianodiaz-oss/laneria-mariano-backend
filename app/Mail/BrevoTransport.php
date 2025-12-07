<?php

namespace App\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

class BrevoTransport extends AbstractTransport
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $payload = [
            'sender' => [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ],
            'to' => array_map(function ($address) {
                return [
                    'email' => $address->getAddress(),
                    'name' => $address->getName() ?: $address->getAddress(),
                ];
            }, $email->getTo()),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody() ?: $email->getTextBody(),
        ];

        $response = Http::withHeaders([
            'api-key' => config('brevo.api_key'),
            'Content-Type' => 'application/json',
            'accept' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (!$response->successful()) {
            throw new \Exception('Brevo API error: ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}