<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\StrongPassword;

class UserController extends Controller
{
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
            'first_login' => 0,
        ]);

        return redirect()->route('dashboard.index')->with('success', 'Password changed successfully.');
    }
}