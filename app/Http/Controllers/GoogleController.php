<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GmailService;
use App\Models\MailToken;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
class GoogleController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();

        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope(Gmail::GMAIL_SEND);
    }

    public function handleCallback(Request $request)
    {
        if (!$request->has('code')) {
            throw new \Exception('Authorization code not found');
        }
        $token = $this->client->fetchAccessTokenWithAuthCode($request->query('code'));
        $this->client->setAccessToken($token);
        $to = '5488@eclisboa.net';
        $subject = 'Test Email from Laravel';
        $htmlContent = view('emails.user-created')->render();

        $boundary = uniqid(rand(), true);
        $subjectCharset = $charset = 'UTF-8';


        $messageBody = "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/html; charset={$charset}\r\n";
        $messageBody .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $messageBody .= "{$htmlContent}\r\n";
        $messageBody .= "--{$boundary}--";

        $rawMessage = "To: {$to}\r\n";
        $rawMessage .= "Subject: =?{$subjectCharset}?B?" . base64_encode($subject) . "?=\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        $rawMessage .= $messageBody;

        $rawMessage = base64_encode($rawMessage);
        $rawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessage);

        $gmailMessage = new Message();
        $gmailMessage->setRaw($rawMessage);


        $service = new Gmail($this->client);
        try {
            $service->users_messages->send('me', $gmailMessage);
            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Exception $e) {
            throw new \Exception('Failed to send email: ' . $e->getMessage());
        }




    }
    public function sendEmailToMultipleRecipiente()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }
    public function redirect(GmailService $gmail)
    {
        return redirect($gmail->authUrl());
    }


}
