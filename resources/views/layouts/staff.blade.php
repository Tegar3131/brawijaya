<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Staff - SIMPB')</title>

    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #7c3aed;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }

        aside {
            background: var(--panel);
            border-right: 1px solid var(--border);
            padding: 24px 18px;
        }

        .brand {
            display: block;
            color: var(--accent);
            font-weight: 700;
            text-decoration: none;
            margin-bottom: 24px;
        }

        .nav-group {
            margin-bottom: 20px;
        }

        .nav-title {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 8px 10px;
        }

        nav {
            display: grid;
            gap: 6px;
        }

        nav a {
            color: var(--muted);
            text-decoration: none;
            padding: 9px 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        nav a:hover {
            background: #f5f3ff;
            color: var(--accent);
        }

        main {
            padding: 28px;
        }

        .top {
            margin-bottom: 20px;
        }

        .role-label {
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 6px 0 8px;
            font-size: 28px;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 20px;
        }

        p, li {
            color: var(--muted);
            line-height: 1.6;
        }

        code {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 13px;
        }
                .auth-checking main {
            opacity: .35;
            pointer-events: none;
        }

        .auth-panel {
            margin-bottom: 18px;
            padding: 12px;
            border: 1px solid var(--border);
            background: #f8fafc;
            border-radius: 10px;
            font-size: 13px;
            color: var(--muted);
        }

        .auth-panel strong {
            color: var(--text);
        }

        .logout-button {
            width: 100%;
            border: 0;
            background: #fee2e2;
            color: #b91c1c;
            padding: 9px 10px;
            border-radius: 8px;
            cursor: pointer;
            text-align: left;
            font-size: 14px;
        }

        .logout-button:disabled {
            opacity: .7;
            cursor: wait;
        }
        @media (max-width: 900px) {
            .shell {
                grid-template-columns: 1fr;
            }

            aside {
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }
        }
    </style>
</head>
<body
    class="auth-checking"
    data-auth-guard="true"
    data-required-roles="admin,pustakawan,kurator"
>
<div class="shell">
    <aside>
        <a class="brand" href="{{ route('staff.dashboard') }}">SIMPB Staff</a>
        <div class="auth-panel">
            <div>Status: <strong data-auth-status>Memeriksa sesi...</strong></div>
            <div>User: <strong data-auth-user-name>-</strong></div>
            <div>Role: <strong data-auth-user-roles>-</strong></div>
        </div>
        <div class="nav-group">
            <div class="nav-title">Umum</div>
            <nav>
                <a href="{{ route('staff.dashboard') }}">Dashboard</a>
                <a href="{{ route('staff.collections.index') }}">Koleksi Internal</a>
                <a href="{{ route('staff.collections.import') }}">⬆ Import Koleksi</a>
            </nav>
        </div>

        <div class="nav-group">
            <div class="nav-title">Perpustakaan</div>
            <nav>
                <a href="{{ route('staff.library.create') }}">Tambah Koleksi Library</a>
                <a href="{{ route('staff.circulation.reservations') }}">Reservasi Sirkulasi</a>
            </nav>
        </div>

        <div class="nav-group">
            <div class="nav-title">Museum</div>
            <nav>
                <a href="{{ route('staff.museum.create') }}">Tambah Koleksi Museum</a>
            </nav>
        </div>

        <div class="nav-group">
            <div class="nav-title">Akun</div>
            <nav>
<button type="button" class="logout-button" data-logout-button>
    Logout
</button>            </nav>
        </div>
    </aside>

    <main>
        <div class="top">
            <div class="role-label">Staff Area</div>
            <h1>@yield('title', 'Staff')</h1>
        </div>

        @yield('content')
    </main>
</div>
@vite('resources/js/auth/frontendAuth.js')
@stack('scripts')
</body>
</html>