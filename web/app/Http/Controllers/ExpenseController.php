<?php

namespace App\Http\Controllers;

use App\Services\FastApiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Harcama ekleme ve listeleme - Tüm veri FastAPI'den.
 */
class ExpenseController extends Controller
{
    public function __construct(
        protected FastApiService $api
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $categories = [];
        try {
            $categories = $this->api->listCategories();
        } catch (\Throwable $e) {
            // API down ise boş form
        }

        return view('expenses.create', ['categories' => $categories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'category_id'  => 'required|integer',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string|max:2000',
            'expense_date' => 'required|date',
        ]);

        try {
            $this->api->createExpense($userId, $request->only(
                'category_id', 'amount', 'description', 'expense_date'
            ));
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $body = $e->response->json();
            $message = $body['detail'] ?? 'Harcama eklenirken hata oluştu.';
            return back()->withErrors(['amount' => $message])->withInput();
        }

        return redirect()->route('expenses.index')->with('success', 'Harcama eklendi.');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $data = ['expenses' => [], 'total' => 0];
        try {
            $data = $this->api->listExpenses($userId, 0, 100);
        } catch (\Throwable $e) {
            // API hata verirse boş liste
        }

        return view('expenses.index', $data);
    }
}
