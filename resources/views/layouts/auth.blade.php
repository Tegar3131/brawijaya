<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login - SIMPB')</title>

    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #1d4ed8;
            --danger: #dc2626;
            --success: #047857;
            --warning: #b45309;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .auth-card {
            width: min(420px, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
        }

        .brand {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        p {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.6;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            margin-bottom: 6px;
        }

        input[aria-invalid="true"] {
            border-color: var(--danger);
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 11px 14px;
            background: var(--accent);
            color: white;
            font-weight: 700;
            cursor: pointer;
            margin-top: 12px;
        }

        button:disabled {
            cursor: wait;
            opacity: .7;
        }

        .note {
            margin-top: 16px;
            padding: 12px;
            border: 1px solid var(--border);
            background: #f8fafc;
            border-radius: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .field-error {
            min-height: 18px;
            margin: 0 0 10px;
            color: var(--danger);
            font-size: 13px;
        }

        .alert {
            display: none;
            margin: 0 0 16px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert.is-visible {
            display: block;
        }

        .alert.error {
            color: var(--danger);
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .alert.success {
            color: var(--success);
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
        }

        .alert.warning {
            color: var(--warning);
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        code {
            background: #f1f5f9;
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 12px;
        }

        .demo-users {
            margin-top: 16px;
            padding-left: 18px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <main class="auth-card">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>