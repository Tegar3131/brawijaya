@extends('layouts.auth')

@section('title', 'Login - SIMPB')

@section('content')
    <a class="brand" href="{{ route('public.home') }}">SIMPB</a>

    <h1>Login</h1>

    <p>
        Masuk menggunakan akun SIMPB untuk mengakses dashboard sesuai role.
    </p>

    <div id="login-alert" class="alert" role="alert"></div>

    <form id="login-form" data-login-form>
        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            autocomplete="email"
            placeholder="member@simpb.test"
            required
        >
        <div id="email-error" class="field-error" data-error-for="email"></div>

        <label for="password">Password</label>
        <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            placeholder="password"
            required
        >
        <div id="password-error" class="field-error" data-error-for="password"></div>

        <button id="login-button" type="submit">
            Login
        </button>
    </form>

    <div class="note">
        Endpoint:
        <br>
        <code>POST /api/auth/login</code>
        <br>
        <code>GET /api/auth/me</code>
    </div>

    <ul class="demo-users">
        <li>Admin: <code>admin@simpb.test</code></li>
        <li>Pustakawan: <code>pustakawan@simpb.test</code></li>
        <li>Kurator: <code>kurator@simpb.test</code></li>
        <li>Member: <code>member@simpb.test</code></li>
        <li>Password demo: <code>password</code></li>
    </ul>
@endsection

@push('scripts')
    @vite('resources/js/pages/auth/login.js')
@endpush