@extends('layouts.app')

@section('title', 'Giriş')

@section('content')
<div class="card" style="max-width: 400px; margin: 2rem auto;">
    <h1 style="margin-top: 0;">Giriş Yap</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label for="email">E-posta</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" required>
            @error('password') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary">Giriş</button>
        <a href="{{ route('register') }}" class="btn btn-secondary">Kayıt ol</a>
    </form>
</div>
@endsection
