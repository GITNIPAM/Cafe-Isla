<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:150']]);

        try {
            Password::sendResetLink(['email' => Str::lower($validated['email']), 'is_active' => true]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);
        }

        return back()->with('info', 'If an active account matches that email, we’ll send a password reset link.');
    }

    public function edit(Request $request, string $token): View
    {
        $email = $request->query('email');

        return view('auth.reset-password', ['email' => is_string($email) ? $email : '', 'token' => $token]);
    }

    public function update(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'regex:/\A[^\x00]{1,72}\z/', 'confirmed'],
        ], [
            'password.regex' => 'Use a password of at most 72 bytes with no null characters.',
        ]);
        $credentials['email'] = Str::lower($credentials['email']);
        $credentials['is_active'] = true;

        $status = Password::reset($credentials, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This reset link is invalid or expired. Request a new one.']);
        }

        return redirect()->route('auth.index')->with('success', 'Password changed. Sign in with your new password.');
    }
}
