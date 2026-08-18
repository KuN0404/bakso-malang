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

@section('title', 'Sesi Berakhir')
@section('code', '419 &middot; Session Expired')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v5l3 3"/>
    </svg>
@endsection

@section('heading', 'Sesi Anda Sudah Berakhir')
@section('description', 'Untuk keamanan, sesi login Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali untuk melanjutkan.')

@section('actions')
    <a href="{{ route('login') }}" class="btn btn-primary">Masuk Kembali</a>
@endsection
