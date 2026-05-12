<?php
// app/Http/Controllers/GoogleController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GmailService;

class GoogleController extends Controller
{
    public function __construct(protected GmailService $gmail) {}

    public function index()
    {
        return view('google.index', [
            'isAuthenticated' => $this->gmail->isAuthenticated(),
        ]);
    }

    public function connect()
    {
        return redirect($this->gmail->authUrl());
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect()->route('google.index')->with('error', 'Authorization failed.');
        }

        $this->gmail->authenticate($request->query('code'));
        return redirect()->route('google.index')->with('success', 'Connected to Gmail!');
    }

    public function sendEmail()
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        try {
            $html = view('emails.user-created')->render();
            $this->gmail->send('5488@eclisboa.net', 'Test Email from Laravel', $html);
            return redirect()->route('google.index')->with('success', 'Email sent!');
        } catch (\Exception $e) {
            return redirect()->route('google.index')->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function listLabels()
    {
        try {
            $labels = $this->gmail->listLabels();
            return view('google.index', ['isAuthenticated' => true, 'labels' => $labels]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function signout()
    {
        $this->gmail->signout();
        return redirect()->route('google.index')->with('success', 'Signed out.');
    }
}