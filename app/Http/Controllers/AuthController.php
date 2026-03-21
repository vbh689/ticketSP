<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (! Auth::user()->is_active) {
                Auth::logout();

                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return view('auth.login');
            }

            return redirect()->route('tickets.index');
        }

        return view('auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [], [
            'login' => 'email hoặc username',
            'password' => 'mật khẩu',
        ]);

        $login = $credentials['login'];
        $attemptCredentials = [
            filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => $login,
            'password' => $credentials['password'],
            'is_active' => true,
        ];

        if (! Auth::attempt($attemptCredentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'Thông tin đăng nhập không chính xác hoặc tài khoản đã bị khóa.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tickets.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
