<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terlalu Banyak Permintaan</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0;
            background: linear-gradient(135deg, #fff1f2, #ffe4e6);
        }
        .card {
            background: white; border-radius: 20px; padding: 48px 40px;
            text-align: center; max-width: 400px; width: 90%;
            box-shadow: 0 20px 60px rgba(220, 38, 38, 0.15);
        }
        .icon {
            font-size: 64px; margin-bottom: 20px; display: block;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 { font-size: 22px; font-weight: 800; color: #111; margin: 0 0 8px; }
        p { font-size: 15px; color: #666; line-height: 1.6; margin: 0 0 24px; }
        .countdown { font-size: 40px; font-weight: 900; color: #dc2626; margin: 16px 0; }
        .btn {
            display: inline-block; background: #dc2626; color: white;
            padding: 12px 28px; border-radius: 12px; font-weight: 700;
            font-size: 15px; text-decoration: none; transition: background .2s;
        }
        .btn:hover { background: #b91c1c; }
    </style>
</head>
<body>
<div class="card">
    <span class="icon">⏱️</span>
    <h1>Terlalu Banyak Permintaan</h1>
    <p>Anda mengirim terlalu banyak permintaan ke server kami. Silakan tunggu sebentar sebelum mencoba kembali.</p>
    <div class="countdown" id="countdown">{{ $retryAfter ?? 60 }}</div>
    <p style="font-size:13px; color:#999;">detik sebelum halaman otomatis dimuat ulang</p>
    <a href="{{ url()->previous() ?: '/' }}" class="btn">Kembali</a>
</div>
<script>
    let seconds = parseInt(document.getElementById('countdown').textContent);
    const interval = setInterval(() => {
        seconds--;
        document.getElementById('countdown').textContent = seconds;
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.reload();
        }
    }, 1000);
</script>
</body>
</html>
