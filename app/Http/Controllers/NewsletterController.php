<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'newsletter_email' => ['required', 'email', 'max:150'],
            'newsletter_consent' => ['accepted'],
        ]);

        DB::table('newsletter_subscriptions')->upsert([
            'email' => Str::lower($validated['newsletter_email']),
            'consented_at' => now(),
        ], ['email'], ['email']);

        return back()->with('success', 'You’re on the Café Isla seasonal email list.');
    }
}
