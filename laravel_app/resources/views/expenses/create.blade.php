@extends('layouts.app')

@section('title', 'Add Expense')

@push('styles')
<style>
    .receipt-upload {
        border: 1px dashed rgba(37,99,235,0.4);
        background: rgba(37,99,235,0.05);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        color: var(--muted);
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        font-size: 14px;
        font-weight: 500;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }
    .receipt-upload:hover {
        border-color: rgba(37,99,235,0.8);
        background: rgba(37,99,235,0.1);
    }
</style>
@endpush

@section('content')
<h1>Add Expense</h1>
<p style="margin-top:-0.35rem; margin-bottom:1rem; color:#6b7280; font-size:0.9rem;">
    Capture a new spending record with category, amount and date details.
</p>
<div class="card" style="margin-bottom:1rem;">
    <h2 style="margin-top:0; margin-bottom:0.75rem; font-size:1.1rem;">Auto Add From Receipt (OCR)</h2>
    <form method="POST" action="{{ route('expenses.ocr.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="receipt">Receipt Photo</label>
            <input type="file" id="receipt" name="receipt" accept="image/*" required style="display:none;">
            <label for="receipt" id="receipt-upload-label" class="receipt-upload">Click to upload receipt</label>
            <div style="font-size:0.82rem; color:#6b7280; margin-top:0.25rem;">
                Upload a clear receipt image. Store, total amount, date and category are selected automatically.
            </div>
            @error('receipt') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary">
            Parse Receipt And Save
        </button>
    </form>
</div>
<div class="card">
    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf
        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat['id'] }}" {{ old('category_id') == $cat['id'] ? 'selected' : '' }}>{{ $cat['name'] }}</option>
                @endforeach
            </select>
            @error('category_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <div style="display:flex; align-items:stretch; gap:0.35rem;">
                <div style="display:flex; align-items:center; justify-content:center; padding:0 0.65rem; background:#e5e7eb; border-radius:8px; border:1px solid #cbd5f5; font-size:0.9rem; color:#111827;">
                    ₺
                </div>
                <input
                    type="number"
                    id="amount"
                    name="amount"
                    step="0.01"
                    min="0.01"
                    value="{{ old('amount') }}"
                    required
                >
            </div>
            @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="expense_date">Expense Date</label>
            <input type="date" id="expense_date" name="expense_date"
                   max="{{ date('Y-m-d') }}"
                   value="{{ old('expense_date', date('Y-m-d')) }}" required>
            @error('expense_date') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
            <button type="submit" class="btn btn-primary">
                Save Expense
            </button>
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('receipt');
    const label = document.getElementById('receipt-upload-label');
    if (!input || !label) return;
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        label.textContent = file ? file.name : 'Click to upload receipt';
    });
})();
</script>
@endpush

