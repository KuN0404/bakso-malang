@php
    try {
        $storeName = \App\Models\Setting::get('store_name', 'Bakso Malang', 'general');
        $logoPath = \App\Models\Setting::get('logo_dark', null, 'general') ?: \App\Models\Setting::get('logo_web', null, 'general');
        $logo = $logoPath ? asset('storage/' . $logoPath) : null;
    } catch (\Throwable $e) {
        $storeName = 'Bakso Malang';
        $logo = null;
    }
    $retryAfter = $retryAfter ?? 60;
@endphp
@extends('errors.layout', ['storeName' => $storeName, 'logo' => $logo])

@section('title', 'Terlalu Banyak Permintaan')
@section('code', '429 &middot; Too Many Requests')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M13 2 3 14h7l-1 8 11-14h-7l1-8z"/>
    </svg>
@endsection

@section('heading', 'Terlalu Banyak Permintaan')
@section('description', 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar sebelum mencoba lagi.')

@section('extra')
    <div class="countdown" id="countdown">{{ $retryAfter }}</div>
    <div class="countdown-label">detik sebelum halaman dimuat ulang</div>
@endsection

@section('actions')
    <a href="{{ url()->previous() ?: '/' }}" class="btn btn-primary">Kembali</a>
@endsection

<script>
    (function () {
        var el = document.getElementById('countdown');
        if (!el) return;
        var seconds = parseInt(el.textContent, 10) || 0;
        var interval = setInterval(function () {
            seconds -= 1;
            el.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.reload();
            }
        }, 1000);
    })();
</script>
