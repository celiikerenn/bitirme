<?php

namespace App\Http\Controllers;

use App\Services\FastApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Giriş ve kayıt - Laravel veritabanına bağlanmaz; FastAPI auth endpoint'lerini kullanır.
 * Başarılı giriş/kayıtta kullanıcı bilgisi session'da tutulur (custom guard yerine manuel).
 */
class AuthController extends Controller
{
    public function __construct(
        protected FastApiService $api
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        try {
            $user = $this->api->login($request->only('email', 'password'));
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $message = $e->response->status() === 401
                ? 'E-posta veya şifre hatalı.'
                : 'Giriş sırasında bir hata oluştu.';
            return back()->withErrors(['email' => $message])->withInput($request->only('email'));
        }

        session([
            'user_id'   => $user['id'],
            'user_name' => $user['name'],
            'user_email'=> $user['email'],
            'user_role' => $user['role'],
        ]);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        try {
            $user = $this->api->register([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
                'role'     => 'user',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $body = $e->response->json();
            $message = $body['detail'] ?? 'Kayıt sırasında bir hata oluştu.';
            if (is_array($message)) {
                $message = implode(' ', $message);
            }
            return back()->withErrors(['email' => $message])->withInput($request->only('name', 'email'));
        }

        session([
            'user_id'   => $user['id'],
            'user_name' => $user['name'],
            'user_email'=> $user['email'],
            'user_role' => $user['role'],
        ]);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
