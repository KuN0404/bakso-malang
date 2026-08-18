@php
    try {
        $storeName = \App\Models\Setting::get('store_name', 'Bakso Malang', 'general');
        $logoPath = \App\Models\Setting::get('logo_dark', null, 'general') ?: \App\Models\Setting::get('logo_web', null, 'general');
        $logo = $logoPath ? asset('storage/' . $logoPath) : null;
    } catch (\Throwable $e) {
        $storeName = 'Bakso Malang';
        $logo = null;
    }
@endphp
@extends('errors.layout', ['storeName' => $storeName, 'logo' => $logo])

@section('title', 'Terjadi Kesalahan')
@section('code', '500 &middot; Server Error')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
    </svg>
@endsection

@section('heading', 'Terjadi Kesalahan pada Server')
@section('description', 'Maaf, ada masalah di sisi kami saat memproses permintaan ini. Tim kami sudah diberi tahu. Silakan coba lagi beberapa saat lagi.')

@section('actions')
    <button type="button" onclick="location.reload()" class="btn btn-primary">Coba Lagi</button>
    <a href="{{ url('/') }}" class="btn btn-secondary">Kembali ke Beranda</a>
@endsection
