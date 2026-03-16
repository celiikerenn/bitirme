@extends('layouts.app')

@section('title', 'Harcama Ekle')

@section('content')
<h1>Harcama Ekle</h1>
<div class="card">
    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf
        <div class="form-group">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id" required>
                <option value="">Seçiniz</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat['id'] }}" {{ old('category_id') == $cat['id'] ? 'selected' : '' }}>{{ $cat['name'] }}</option>
                @endforeach
            </select>
            @error('category_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Tutar (₺)</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required>
            @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="expense_date">Harcama Tarihi</label>
            <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
            @error('expense_date') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="description">Açıklama (opsiyonel)</label>
            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">İptal</a>
    </form>
</div>
@endsection
