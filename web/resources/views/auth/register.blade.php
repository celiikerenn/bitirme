@extends('layouts.app')

@section('title', 'Kayıt')

@section('content')
<div class="card" style="max-width: 400px; margin: 2rem auto;">
    <h1 style="margin-top: 0;">Kayıt Ol</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
            <label for="name">Ad Soyad</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="email">E-posta</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" required minlength="6">
            @error('password') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Şifre (tekrar)</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary">Kayıt ol</button>
        <a href="{{ route('login') }}" class="btn btn-secondary">Giriş yap</a>
    </form>
</div>
@endsection
