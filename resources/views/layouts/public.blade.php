<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIMPB')</title>

    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #1d4ed8;
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

        header {
            background: var(--panel);
            border-bottom: 1px solid var(--border);
        }

        .container {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
        }

        .topbar {
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
        }

        nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        nav a {
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            color: var(--accent);
        }

        main {
            padding: 32px 0;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
        }

        .eyebrow {
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 20px;
        }

        p {
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

        ul {
            padding-left: 20px;
        }

        li {
            margin: 8px 0;
            color: var(--muted);
        }

        footer {
            color: var(--muted);
            font-size: 13px;
            padding: 24px 0 40px;
        }
    </style>
</head>
<body>
<header>
    <div class="container topbar">
        <a class="brand" href="{{ route('public.home') }}">SIMPB</a>

        <nav>
            <a href="{{ route('public.home') }}">Beranda</a>
            <a href="{{ route('public.catalog') }}">Katalog</a>
            <a href="{{ route('public.categories') }}">Kategori</a>
            <a href="{{ route('auth.login') }}">Login</a>
        </nav>
    </div>
</header>

<main>
    <div class="container">
        @yield('content')
    </div>
</main>

<footer>
    <div class="container">
        SIMPB - Sistem Informasi Museum dan Perpustakaan Brawijaya
    </div>
</footer>
@stack('scripts')
</body>
</html>