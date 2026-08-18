<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') &mdash; {{ $storeName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-dark: #1e3a5f;
            --brand-light: #2a4a73;
            --brand-accent: #2563eb;
            --brand-accent-hover: #1d4ed8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-light));
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 28px;
            padding: 40px 36px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 28px;
        }
        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            object-fit: cover;
            background: #f1f5f9;
        }
        .brand-fallback {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: var(--brand-dark);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }
        .brand-name {
            font-weight: 700;
            font-size: 15px;
            color: #0f172a;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 18px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--brand-accent);
            font-weight: 700;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 999px;
            background: #eff6ff;
            color: var(--brand-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon-wrap svg {
            width: 36px;
            height: 36px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 21px;
            font-weight: 700;
            color: #0f172a;
        }
        .desc {
            margin: 0 0 28px;
            font-size: 14px;
            line-height: 1.7;
            color: #64748b;
        }
        .extra {
            margin: -8px 0 24px;
        }
        .countdown {
            font-size: 40px;
            font-weight: 800;
            color: var(--brand-accent);
            margin: 0 0 4px;
        }
        .countdown-label {
            font-size: 12px;
            color: #94a3b8;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color .15s ease, transform .1s ease;
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary {
            background: var(--brand-accent);
            color: #ffffff;
        }
        .btn-primary:hover { background: var(--brand-accent-hover); }
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
        }
        .btn-secondary:hover { background: #e2e8f0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $storeName }}" class="brand-logo">
            @else
                <span class="brand-fallback">{{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}</span>
            @endif
            <span class="brand-name">{{ $storeName }}</span>
        </div>

        <span class="badge">@yield('code')</span>

        <div class="icon-wrap">
            @yield('icon')
        </div>

        <h1>@yield('heading')</h1>
        <p class="desc">@yield('description')</p>

        <div class="extra">@yield('extra')</div>

        <div class="actions">@yield('actions')</div>
    </div>
</body>
</html>
