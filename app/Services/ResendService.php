<?php

namespace App\Services;

use Resend;

class ResendService
{
    private $client;

    public function __construct()
    {
        $this->client = Resend::client(config('services.resend.key'));
    }

    public function sendViewEmail(string $to, string $subject, string $view, array $data = []): void
    {
        $html = view($view, $data)->render();

        $this->client->emails->send([
            'from' => 'InternHub <onboarding@resend.dev>',
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ]);
    }
}