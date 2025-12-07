<?php

namespace App\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Illuminate\Support\Facades\Http;

class BrevoTransport extends AbstractTransport
{
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
            'htmlContent' => $email->getHtmlBody(),
        ];

        Http::withHeaders([
            'api-key' => config('brevo.api_key'),
            'Content-Type' => 'application/json',
        ])->post(config('brevo.endpoint'), $payload);
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}