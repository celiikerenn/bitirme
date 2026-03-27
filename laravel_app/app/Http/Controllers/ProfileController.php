<?php

namespace App\Http\Controllers;

use App\Services\FastApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected FastApiService $api
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        return view('profile', [
            'name'          => $request->session()->get('user_name'),
            'email'         => $request->session()->get('user_email'),
        ]);
    }

    public function updateBudget(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $monthlyBudget = (float) ($validated['monthly_budget'] ?? 0);

        // Bütçeyi FastAPI tarafında güncelle (kullanıcıya bağlı alan)
        $this->api->updateUserBudget($userId, $monthlyBudget);

        // Dashboard hesaplamaları için session'da da tut
        $request->session()->put('monthly_budget', $monthlyBudget);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Budget updated successfully.');
    }

    public function showBudget(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $monthlyBudget = (float) ($request->session()->get('monthly_budget', 0));

        return view('profile.budget', [
            'monthlyBudget' => $monthlyBudget,
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        try {
            $this->api->changePassword([
                'user_id'          => $userId,
                'current_password' => $request->input('current_password'),
                'new_password'     => $request->input('new_password'),
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $body = $e->response->json();
            $detail = $body['detail'] ?? null;
            $message = is_string($detail) ? $detail : 'Password change failed.';
            return back()->withErrors(['current_password' => $message]);
        } catch (\Throwable $e) {
            return back()->withErrors(['current_password' => 'Password change failed.']);
        }

        return back()->with('success', 'Password changed successfully.');
    }
}

