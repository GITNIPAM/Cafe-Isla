<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isOperator() ? 'admin.index' : 'account.index');
        }

        return view('auth.index');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($request, $user);

        return redirect()->route('account.index')->with('success', 'Welcome to Café Isla, '.$user->name.'.');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email or password does not match our records.');
        }

        if (! $user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'This account has been deactivated by an administrator.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($request, $user);

        if ($user->isOperator()) {
            return redirect()->route('admin.index')->with('success', 'Signed in as '.ucfirst($user->role).' ('.$user->name.').');
        }

        return redirect()->intended(route('account.index'))->with('success', 'Welcome back, '.$user->name.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Signed out cleanly.');
    }
}
