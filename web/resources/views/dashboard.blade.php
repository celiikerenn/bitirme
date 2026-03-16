@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1>Dashboard</h1>
<p>Hoş geldin, <strong>{{ $userName }}</strong>.</p>

<div class="card">
    <h2 style="margin-top: 0;">Bu Ay Özeti ({{ $currentYear }}-{{ str_pad($currentMonth, 2, '0', STR_PAD_LEFT) }})</h2>
    @if(!empty($monthly))
        <p class="monthly-summary">
            Toplam harcama: <strong>{{ number_format($monthly['total_amount'] ?? 0, 2, ',', '.') }} ₺</strong><br>
            Harcama adedi: <strong>{{ $monthly['expense_count'] ?? 0 }}</strong>
        </p>
    @else
        <p>Bu ay henüz harcama kaydı yok veya API'ye ulaşılamıyor.</p>
    @endif
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">Harcama Ekle</a>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Tüm Harcamalar</a>
</div>
@endsection
