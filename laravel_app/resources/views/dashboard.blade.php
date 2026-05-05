@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1>Dashboard</h1>
<p>Welcome, <strong>{{ $userName }}</strong>.</p>

<div class="card" style="display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-start;">
    <div style="flex:1 1 260px;">
        @php
            $monthlyBudget = (float) session('monthly_budget', 0);
            $spent = (float)($monthly['total_amount'] ?? 0);
            $usagePercent = $monthlyBudget > 0 ? ($spent / $monthlyBudget * 100) : 0;
            $usageRounded = $monthlyBudget > 0 ? min(200, round($usagePercent)) : 0;
        @endphp

        <h2 style="margin-top: 0; margin-bottom:0.75rem;">
            This Month Summary ({{ $currentYear }}-{{ str_pad($currentMonth, 2, '0', STR_PAD_LEFT) }})
        </h2>
        @if(!empty($monthly))
            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
                <div style="flex:1 1 0; min-width:150px;">
                    <a href="{{ route('profile.budget.show') }}" style="text-decoration:none; color:inherit; display:block; height:100%;">
                        <div style="background:var(--surface); border-radius:16px; border:1px solid var(--border); padding:14px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; cursor:pointer; box-shadow:none !important; filter:none !important;">
                            <div class="section-label" style="margin-bottom:0.15rem;">
                                Total budget
                            </div>
                            <div class="number-value stat-countup" data-value="{{ $monthlyBudget }}" data-currency="1" style="font-size:24px; font-weight:600;">
                                @if($monthlyBudget > 0)
                                    {{ number_format($monthlyBudget, 2, ',', '.') }} ₺
                                @else
                                    N/A
                                @endif
                            </div>
                            <div style="font-size:12px; color:var(--muted); margin-top:0.15rem;">
                                Click to edit
                            </div>
                        </div>
                    </a>
                </div>
                <div style="flex:1 1 0; min-width:150px;">
                    <div style="background:var(--surface); border-radius:16px; border:1px solid var(--border); padding:14px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; box-shadow:none !important; filter:none !important;">
                        <div class="section-label" style="margin-bottom:0.15rem;">
                            Total spent
                        </div>
                        <div class="number-value stat-countup" data-value="{{ $spent }}" data-currency="1" style="font-size:24px; font-weight:600;">
                            {{ number_format($spent, 2, ',', '.') }} ₺
                        </div>
                    </div>
                </div>
                <div style="flex:1 1 0; min-width:150px;">
                    <div style="background:var(--surface); border-radius:16px; border:1px solid var(--border); padding:14px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; box-shadow:none !important; filter:none !important;">
                        <div class="section-label" style="margin-bottom:0.15rem;">
                            Expenses
                        </div>
                        <div class="number-value stat-countup" data-value="{{ (int)($monthly['expense_count'] ?? 0) }}" style="font-size:24px; font-weight:600;">
                            {{ $monthly['expense_count'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:0.9rem;">
                @if($monthlyBudget > 0)
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--muted); margin-bottom:0.25rem;">
                        <span>Budget usage</span>
                        <span class="number-value" id="budget-usage-label">0%</span>
                    </div>
                    <div class="budget-bar">
                        <div class="budget-bar-fill" id="budget-bar-fill" data-target="{{ min(100, max(0, $usagePercent)) }}"></div>
                    </div>
                    @if($usagePercent > 100)
                        <p style="margin-top:0.45rem; font-size:0.9rem; color:var(--red); font-weight:600;">
                            You have exceeded your monthly budget!
                        </p>
                    @elseif($usagePercent > 80)
                        <p style="margin-top:0.45rem; font-size:0.9rem; color:#86efac;">
                            You have used over 80% of your budget.
                        </p>
                    @endif
                @else
                    <p style="margin-top:0.45rem; font-size:0.9rem; color:var(--muted);">
                        No monthly budget limit is set.
                    </p>
                @endif
            </div>
        @else
            <p>No expenses yet for this month, or the API is not reachable.</p>
        @endif
        <a href="{{ route('expenses.create') }}" class="btn btn-primary">Add Expense</a>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">All Expenses</a>
    </div>
</div>

@if(!empty($recentMonths))
    <div class="card">
        <h2 style="margin-top:0; margin-bottom:0.75rem;">Recent Months</h2>
        <div style="display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:0.75rem;">
            @foreach($recentMonths as $m)
                <div>
                    <div style="background:var(--surface2); border-radius:0.9rem; padding:0.6rem 0.75rem; border:1px solid var(--border);">
                        <div style="font-weight:600; font-size:0.9rem; margin-bottom:0.25rem; color:var(--txt);">
                            {{ $m['year'] }}-{{ str_pad($m['month'], 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <div style="font-size:0.85rem; color:var(--muted); margin-bottom:0.15rem;">
                            Total spent
                        </div>
                        <div class="number-value" style="font-size:22px; font-weight:600; margin-bottom:0.35rem;">
                            {{ number_format($m['total'], 2, ',', '.') }} ₺
                        </div>
                        <div style="font-size:0.8rem; color:var(--muted);">
                            {{ $m['count'] }} expenses
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @php
            $totalMonths = isset($recentMonthsTotal) ? $recentMonthsTotal : null;
        @endphp
        <div style="margin-top:0.75rem; display:flex; gap:0.5rem;">
            @if($monthPage > 1)
                <a href="{{ route('dashboard', ['m_page' => $monthPage - 1]) }}"
                   class="btn btn-secondary" style="padding:0.25rem 0.6rem; font-size:0.85rem;">
                    ‹ Previous
                </a>
            @endif
            @if(isset($recentMonths[0]) && count($recentMonths) === ($perMonthPage ?? 12))
                <a href="{{ route('dashboard', ['m_page' => $monthPage + 1]) }}"
                   class="btn btn-secondary" style="padding:0.25rem 0.6rem; font-size:0.85rem;">
                    Next ›
                </a>
            @endif
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const easeOutQuart = (t) => 1 - Math.pow(1 - t, 4);
    const countNodes = document.querySelectorAll(".stat-countup");
    if (countNodes.length) {
        const start = performance.now();
        const duration = 900;
        function step(now) {
            const p = Math.min((now - start) / duration, 1);
            const eased = easeOutQuart(p);
            countNodes.forEach((node) => {
                const target = Number(node.dataset.value || 0);
                const value = target * eased;
                if (node.dataset.currency === "1") {
                    node.textContent = `${value.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺`;
                } else {
                    node.textContent = Math.round(value).toLocaleString('tr-TR');
                }
            });
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    const fill = document.getElementById("budget-bar-fill");
    const label = document.getElementById("budget-usage-label");
    if (fill && label) {
        const target = Math.max(0, Math.min(100, Number(fill.dataset.target || 0)));
        fill.style.background = target > 80
            ? "linear-gradient(90deg, #4ade80, #86efac)"
            : "linear-gradient(90deg, #15803d, #22c55e)";
        requestAnimationFrame(() => { fill.style.width = `${target}%`; });

        const start = performance.now();
        const duration = 900;
        function step(now) {
            const p = Math.min((now - start) / duration, 1);
            const eased = easeOutQuart(p);
            const current = target * eased;
            label.textContent = `${current.toLocaleString('tr-TR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        if (target > 80 && window.appToast) {
            window.appToast("Budget usage exceeded 80%.", "warning");
        }
    }
})();
</script>
@endpush

