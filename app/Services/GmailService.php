<?php
// app/Services/GmailService.php

namespace App\Services;

use App\Models\GoogleToken;
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
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function authUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->client->setAccessToken($token);
        GoogleToken::updateOrCreate(['id' => 1], ['token' => json_encode($token)]);
    }

    public function bootClient(): bool
    {
        $record = GoogleToken::latest()->first();

        if (!$record) return false;

        $token = json_decode($record->token, true);
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();

            if (!$refreshToken) return false;

            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($newToken['error'])) return false;

            if (!isset($newToken['refresh_token'])) {
                $newToken['refresh_token'] = $refreshToken;
            }

            $record->update(['token' => json_encode($newToken)]);
            $this->client->setAccessToken($newToken);
        }

        return true;
    }

    public function send(string $to, string $subject, string $htmlContent): void
    {
        if (!$this->bootClient()) {
            throw new \Exception('Gmail not authenticated.');
        }

        $boundary = uniqid(rand(), true);
        $charset  = 'UTF-8';

        $messageBody  = "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/html; charset={$charset}\r\n";
        $messageBody .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $messageBody .= "{$htmlContent}\r\n";
        $messageBody .= "--{$boundary}--";

        $rawMessage  = "To: {$to}\r\n";
        $rawMessage .= "Subject: =?{$charset}?B?" . base64_encode($subject) . "?=\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        $rawMessage .= $messageBody;

        $raw = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Message();
        $message->setRaw($raw);

        (new Gmail($this->client))->users_messages->send('me', $message);
    }

    public function listLabels(): array
    {
        if (!$this->bootClient()) {
            throw new \Exception('Gmail not authenticated.');
        }

        $results = (new Gmail($this->client))->users_labels->listUsersLabels('me');
        return $results->getLabels();
    }

    public function signout(): void
    {
        $record = GoogleToken::latest()->first();

        if ($record) {
            $token = json_decode($record->token, true);
            $this->client->revokeToken($token['access_token'] ?? null);
            $record->delete();
        }
    }

    public function isAuthenticated(): bool
    {
        return $this->bootClient();
    }
}