<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GmailService;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GoogleController extends Controller
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

    // Show main page
    public function index()
    {
        $isAuthenticated = session()->has('gmail_token');
        return view('google.index', compact('isAuthenticated'));
    }

    // Redirect to Google OAuth
    public function connect()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }

    

    // Handle OAuth callback + send email
    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect()->route('google.index')->with('error', 'Authorization failed.');
        }

        $token = $this->client->fetchAccessTokenWithAuthCode($request->query('code'));
        $this->client->setAccessToken($token);

        // Store token in session
        session(['gmail_token' => $token]);

        // Send email after successful auth
        $to        = '5488@eclisboa.net';
        $subject   = 'Test Email from Laravel';
        $htmlContent = view('emails.user-created')->render();

        $boundary       = uniqid(rand(), true);
        $charset        = 'UTF-8';

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

        $rawMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $gmailMessage = new Message();
        $gmailMessage->setRaw($rawMessage);

        $service = new Gmail($this->client);

        try {
            $service->users_messages->send('me', $gmailMessage);
            return redirect()->route('google.index')->with('success', 'Email sent successfully!');
        } catch (\Exception $e) {
            return redirect()->route('google.index')->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    // Sign out + revoke token
    public function signout()
    {
        $token = session('gmail_token');

        if ($token) {
            $this->client->revokeToken($token['access_token']);
            session()->forget('gmail_token');
        }

        return redirect()->route('google.index')->with('success', 'Signed out successfully.');
    }

    // List Gmail labels
    public function listLabels()
    {
        $token = session('gmail_token');

        if (!$token) {
            return redirect()->route('google.connect');
        }

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            return redirect()->route('google.connect')->with('error', 'Token expired, please re-authenticate.');
        }

        try {
            $service = new Gmail($this->client);
            $results = $service->users_labels->listUsersLabels('me');
            $labels  = $results->getLabels();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('google.index', [
            'isAuthenticated' => true,
            'labels'          => $labels,
        ]);

        
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