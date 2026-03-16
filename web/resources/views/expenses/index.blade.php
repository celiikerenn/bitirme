@extends('layouts.app')

@section('title', 'Harcama Listesi')

@section('content')
<h1>Harcamalarım</h1>
<p><a href="{{ route('expenses.create') }}" class="btn btn-primary">Yeni Harcama Ekle</a></p>

<div class="card">
    @if(count($expenses) > 0)
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kategori</th>
                    <th>Açıklama</th>
                    <th class="text-right">Tutar</th>
                    <th>Eklenme</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $e)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($e['expense_date'])->format('d.m.Y') }}</td>
                        <td>{{ $e['category_name'] }}</td>
                        <td>{{ Str::limit($e['description'] ?? '-', 40) }}</td>
                        <td class="text-right">{{ number_format($e['amount'], 2, ',', '.') }} ₺</td>
                        <td>{{ \Carbon\Carbon::parse($e['created_at'])->format('d.m.Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 1rem;">Toplam <strong>{{ $total }}</strong> kayıt.</p>
    @else
        <p>Henüz harcama kaydı yok. <a href="{{ route('expenses.create') }}">İlk harcamanı ekle</a>.</p>
    @endif
</div>
@endsection
