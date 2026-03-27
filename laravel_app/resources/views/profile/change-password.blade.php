@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<h1>Change Password</h1>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            {{ $error }}<br>
        @endforeach
    </div>
@endif

<div class="card" style="max-width:480px;">
    <form method="POST" action="{{ route('profile.change-password.update') }}">
        @csrf

        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input
                type="password"
                id="current_password"
                name="current_password"
                required
            >
            @error('current_password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input
                type="password"
                id="new_password"
                name="new_password"
                required
            >
            @error('new_password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="new_password_confirmation">Confirm New Password</label>
            <input
                type="password"
                id="new_password_confirmation"
                name="new_password_confirmation"
                required
            >
        </div>

        <button type="submit" class="btn btn-primary">
            Change Password
        </button>
    </form>
</div>
@endsection

