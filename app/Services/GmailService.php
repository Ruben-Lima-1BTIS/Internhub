<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));

        $this->client->addScope(Gmail::GMAIL_SEND);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent'); // ensures refresh_token
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessToken(string $code): array
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function setToken(array $token): void
    {
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired() && isset($token['refresh_token'])) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            $this->client->setAccessToken($newToken);
        }
    }

    public function send(string $to, string $subject, string $html): void
    {
        $gmail = new Gmail($this->client);

        $raw = "To: {$to}\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $raw .= $html;

        $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $message = new Message();
        $message->setRaw($encoded);

        $gmail->users_messages->send('me', $message);
    }
}