@extends('layouts.app')

@section('title', 'Expense List')

@push('styles')
<style>
    .expenses-table-wrap {
        background: var(--surface);
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(37,99,235,0.06);
        overflow: hidden;
    }
    .expenses-table thead { background: var(--surface2); border-bottom: 1px solid var(--border2); }
    .expenses-table th {
        padding: 12px 16px;
        font-size: 11px !important;
        color: var(--muted) !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .expenses-table tbody tr,
    .expenses-table tbody tr:hover,
    .expenses-table tbody tr:active,
    .expenses-table tbody tr:focus {
        background: transparent !important;
    }
    .expenses-table td {
        padding: 14px 16px;
        font-size: 13px;
        font-weight: 400;
        color: var(--txt);
        border-bottom: 1px solid var(--border);
    }
    .expenses-table tbody tr:last-child td { border-bottom: none; }
    .amount-cell { font-family: 'DM Mono', monospace; font-weight: 600; color: var(--txt); }
    .date-cell { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); }
    .expense-actions {
        display:flex;
        align-items:center;
        gap:6px;
        justify-content:flex-end;
    }
    .expense-actions .btn {
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: none !important;
        transform: none !important;
    }
    .expense-actions .btn-secondary {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid rgba(37,99,235,0.2);
    }
    .expense-actions .btn-secondary:hover { background: #dbeafe; }
    .expense-actions .btn-delete-trigger,
    .expense-actions .delete-confirm {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid rgba(239,68,68,0.2);
    }
    .expense-actions .btn-delete-trigger:hover,
    .expense-actions .delete-confirm:hover { background: #fee2e2; }
    .delete-confirm-ui { display:none; gap:6px; }
    .delete-confirm-ui.show { display:inline-flex; }
    .row-deleting { opacity:0; transform: translateY(-6px); transition: opacity 0.2s ease, transform 0.2s ease; }
</style>
@endpush

@section('content')
<h1>My Expenses</h1>
<p style="margin-bottom:0.75rem; color:var(--muted); font-size:0.9rem;">
    Review, edit or delete your past expenses. Use this list to keep your spending history up to date.
</p>

<div class="card" style="padding:1rem; margin-bottom:1rem;">
    <form method="GET" action="{{ route('expenses.index') }}" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
        <label for="month" style="font-size:0.9rem; color:#374151;">Month:</label>
        <select id="month" name="month" onchange="this.form.submit()"
                style="padding:0.3rem 0.5rem; border-radius:8px; font-size:0.9rem;">
            @if(!empty($months ?? []))
                @foreach($months as $month)
                    <option value="{{ $month }}" {{ ($selectedMonth ?? null) === $month ? 'selected' : '' }}>
                        {{ $month }}
                    </option>
                @endforeach
            @endif
        </select>
    </form>
</div>

<p><a href="{{ route('expenses.create') }}" class="btn btn-primary">Add New Expense</a></p>

<div class="card expenses-table-wrap">
    @if(count($expenses) > 0)
        <table class="expenses-table">
            <thead>
                <tr>
                    <th style="text-transform:uppercase; letter-spacing:0.06em; font-size:0.75rem;">Date</th>
                    <th style="text-transform:uppercase; letter-spacing:0.06em; font-size:0.75rem;">Category</th>
                    <th style="text-transform:uppercase; letter-spacing:0.06em; font-size:0.75rem;">Description</th>
                    <th class="text-right" style="text-transform:uppercase; letter-spacing:0.06em; font-size:0.75rem;">Amount</th>
                    <th style="text-transform:uppercase; letter-spacing:0.06em; font-size:0.75rem;">Created</th>
                    <th style="text-transform:uppercase; letter-spacing:0.06em; font-size:0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $e)
                    @php
                        $cat = strtolower($e['category_name'] ?? 'other');
                        $badgeClass = 'badge-other';
                        if (str_contains($cat, 'food') || str_contains($cat, 'yemek')) $badgeClass = 'badge-food';
                        elseif (str_contains($cat, 'transport') || str_contains($cat, 'ulaşım') || str_contains($cat, 'ulasim')) $badgeClass = 'badge-transport';
                        elseif (str_contains($cat, 'bill') || str_contains($cat, 'fatura')) $badgeClass = 'badge-bills';
                        elseif (str_contains($cat, 'shop') || str_contains($cat, 'market') || str_contains($cat, 'giyim')) $badgeClass = 'badge-shopping';
                        elseif (str_contains($cat, 'health') || str_contains($cat, 'sağlık') || str_contains($cat, 'saglik')) $badgeClass = 'badge-health';
                        elseif (str_contains($cat, 'entertainment') || str_contains($cat, 'eğlence') || str_contains($cat, 'eglence')) $badgeClass = 'badge-entertainment';
                        elseif (str_contains($cat, 'education') || str_contains($cat, 'egitim') || str_contains($cat, 'eğitim')) $badgeClass = 'badge-education';
                        elseif (str_contains($cat, 'clothing')) $badgeClass = 'badge-clothing';
                        elseif (str_contains($cat, 'rent') || str_contains($cat, 'kira')) $badgeClass = 'badge-rent';
                    @endphp
                    <tr class="expense-row"
                        data-category="{{ strtolower($e['category_name'] ?? '') }}"
                        data-search="{{ strtolower(($e['description'] ?? '') . ' ' . ($e['category_name'] ?? '') . ' ' . number_format($e['amount'], 2, ',', '.') . ' ' . number_format($e['amount'], 2, '.', '') . ' ' . \Carbon\Carbon::parse($e['expense_date'])->format('d.m.Y') . ' ' . \Carbon\Carbon::parse($e['expense_date'])->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($e['created_at'])->format('d.m.Y H:i')) }}">
                        <td class="date-cell">{{ \Carbon\Carbon::parse($e['expense_date'])->format('d.m.Y') }}</td>
                        <td>
                            <span class="badge-category {{ $badgeClass }}">
                                {{ $e['category_name'] }}
                            </span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($e['description'] ?? '-', 40) }}</td>
                        <td class="text-right amount-cell">
                            {{ number_format($e['amount'], 2, ',', '.') }} ₺
                        </td>
                        <td class="date-cell">{{ \Carbon\Carbon::parse($e['created_at'])->format('d.m.Y H:i') }}</td>
                        <td>
                            <div class="expense-actions">
                                <a class="btn btn-secondary" href="{{ route('expenses.edit', $e['id']) }}">Edit</a>
                                <button type="button" class="btn btn-secondary btn-delete-trigger">Delete</button>
                                <span class="delete-confirm-ui">
                                    <button type="button" class="btn delete-confirm">Confirm</button>
                                    <button type="button" class="btn delete-cancel">Cancel</button>
                                </span>
                                <form method="POST" action="{{ route('expenses.destroy', $e['id']) }}" style="display:none;" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 1rem;">
            <strong>{{ count($expenses) }}</strong> of <strong>{{ $total }}</strong> records.
        </p>

        @if(($totalPages ?? 1) > 1)
            @php
                $current = $page ?? 1;
                $last = $totalPages ?? 1;
            @endphp
            <nav aria-label="Pagination" style="margin-top:0.75rem;" class="expenses-pagination">
                <ul style="list-style:none; padding-left:0; display:flex; gap:0.35rem; flex-wrap:wrap;">
                    @if($current > 1)
                        <li>
                            <a href="{{ route('expenses.index', ['page' => $current - 1, 'month' => $selectedMonth ?? null]) }}"
                               class="btn btn-secondary"
                               style="padding:0.25rem 0.6rem; font-size:0.85rem; border-radius:999px;">
                                ‹ Prev
                            </a>
                        </li>
                    @endif

                    @for($p = 1; $p <= $last; $p++)
                        <li>
                            @if($p === $current)
                                <span class="btn btn-primary"
                                      style="padding:0.25rem 0.6rem; font-size:0.85rem; border-radius:999px;">
                                    {{ $p }}
                                </span>
                            @else
                                <a href="{{ route('expenses.index', ['page' => $p, 'month' => $selectedMonth ?? null]) }}"
                                   class="btn btn-secondary"
                                   style="padding:0.25rem 0.6rem; font-size:0.85rem; border-radius:999px;">
                                    {{ $p }}
                                </a>
                            @endif
                        </li>
                    @endfor

                    @if($current < $last)
                        <li>
                            <a href="{{ route('expenses.index', ['page' => $current + 1, 'month' => $selectedMonth ?? null]) }}"
                               class="btn btn-secondary"
                               style="padding:0.25rem 0.6rem; font-size:0.85rem; border-radius:999px;">
                                Next ›
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    @else
        <p>No expenses yet. <a href="{{ route('expenses.create') }}">Add your first expense</a>.</p>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const rows = Array.from(document.querySelectorAll(".expense-row"));

    let currentConfirmRow = null;
    rows.forEach((row) => {
        const trigger = row.querySelector(".btn-delete-trigger");
        const confirmWrap = row.querySelector(".delete-confirm-ui");
        const confirmBtn = row.querySelector(".delete-confirm");
        const cancelBtn = row.querySelector(".delete-cancel");
        const form = row.querySelector(".delete-form");
        if (!trigger || !confirmWrap || !confirmBtn || !cancelBtn || !form) return;

        trigger.addEventListener("click", () => {
            if (currentConfirmRow && currentConfirmRow !== row) {
                currentConfirmRow.querySelector(".delete-confirm-ui")?.classList.remove("show");
            }
            confirmWrap.classList.add("show");
            currentConfirmRow = row;
        });

        cancelBtn.addEventListener("click", () => {
            confirmWrap.classList.remove("show");
            if (currentConfirmRow === row) currentConfirmRow = null;
        });

        confirmBtn.addEventListener("click", () => {
            row.classList.add("row-deleting");
            setTimeout(() => form.submit(), 220);
        });
    });
})();
</script>
@endpush
