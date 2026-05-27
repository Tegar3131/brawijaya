<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIMPB Brawijaya')</title>

    <!-- Google Fonts for elegant museum typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Museum Color Palette */
            --bg: #faf9f6; /* Ivory / Off-white */
            --panel: #ffffff;
            --text: #1c1917; /* Charcoal */
            --muted: #57534e; /* Warm Gray */
            --border: #e7e5e4;
            --accent: #c29b40; /* Gold */
            --accent-hover: #b08933;
            --brand-dark: #2f3e2b; /* Army Green */
            
            /* Typography */
            --font-heading: 'Cinzel', serif;
            --font-serif: 'Lora', serif;
            --font-sans: 'Inter', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
        }

        /* Elegant Header */
        header {
            background: var(--brand-dark);
            color: white;
            border-bottom: 3px solid var(--accent);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .container {
            width: min(1200px, calc(100% - 40px));
            margin: 0 auto;
        }

        .topbar {
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            font-family: var(--font-heading);
            font-weight: 700;
            color: white;
            text-decoration: none;
            font-size: 24px;
            letter-spacing: 0.05em;
        }

        .brand span {
            color: var(--accent);
        }

        nav {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        nav a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: color 0.2s;
        }

        nav a:hover, nav a.active {
            color: var(--accent);
        }

        main {
            padding: 48px 0 80px;
            min-height: 70vh;
        }

        /* Cards & Containers */
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border-radius: 4px; /* Minimal border radius for classic look */
        }

        .eyebrow {
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 12px;
            font-family: var(--font-sans);
        }

        h1, h2, h3, h4 {
            font-family: var(--font-heading);
            font-weight: 700;
            color: var(--brand-dark);
            line-height: 1.3;
        }

        h1 { margin: 0 0 16px; font-size: 38px; }
        h2 { margin: 0 0 16px; font-size: 28px; }
        h3 { font-size: 20px; }

        p {
            color: var(--muted);
            margin: 0 0 16px;
            font-family: var(--font-serif);
            font-size: 16px;
            line-height: 1.8;
        }

        a {
            color: var(--accent);
            text-decoration: none;
            transition: color 0.2s;
        }

        a:hover {
            color: var(--accent-hover);
        }

        /* Forms & Inputs */
        input, select, textarea {
            font-family: var(--font-sans);
            background: var(--bg);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px rgba(194, 155, 64, 0.1);
        }

        button {
            font-family: var(--font-sans);
        }

        code {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin: 8px 0;
            color: var(--muted);
            font-family: var(--font-serif);
        }

        /* Footer */
        footer {
            background: var(--brand-dark);
            color: rgba(255, 255, 255, 0.6);
            padding: 60px 0;
            font-size: 14px;
            border-top: 4px solid var(--accent);
            text-align: center;
        }
        
        footer .footer-brand {
            font-family: var(--font-heading);
            color: white;
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        footer .footer-brand span {
            color: var(--accent);
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 24px;
            }
            h1 { font-size: 28px; }
        }
    </style>
</head>
<body>
<header>
    <div class="container topbar">
        <a class="brand" href="{{ route('public.home') }}">SIMPB <span>SISTEM INFORMASI MUSEUM DAN PERPUSTAKAAN</span></a>

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
        <div class="footer-brand">SIMPB <span>Brawijaya</span></div>
        <p style="color: rgba(255,255,255,0.6); font-family: var(--font-sans); font-size: 14px; margin-bottom: 0;">Sistem Informasi Museum dan Perpustakaan Brawijaya</p>
        <p style="color: rgba(255,255,255,0.4); font-family: var(--font-sans); font-size: 12px;">&copy; {{ date('Y') }} All Rights Reserved.</p>
    </div>
</footer>
@stack('scripts')
</body>
</html>