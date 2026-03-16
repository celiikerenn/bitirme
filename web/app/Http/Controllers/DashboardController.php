<?php

namespace App\Http\Controllers;

use App\Services\FastApiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard - Bu ay özeti ve hızlı linkler.
 * Veri FastAPI'den alınır; Laravel veritabanına erişmez.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected FastApiService $api
    ) {}

    public function index(Request $request): View
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $now = now();
        $monthly = [];
        try {
            $monthly = $this->api->getMonthlyTotal($userId, $now->year, $now->month);
        } catch (\Throwable $e) {
            // API erişilemezse boş göster
        }

        return view('dashboard', [
            'userName'   => $request->session()->get('user_name'),
            'monthly'    => $monthly,
            'currentYear'  => $now->year,
            'currentMonth' => $now->month,
        ]);
    }
}
