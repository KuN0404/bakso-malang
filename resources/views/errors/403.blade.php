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

@section('title', 'Akses Ditolak')
@section('code', '403 &middot; Forbidden')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <rect x="4" y="10" width="16" height="10" rx="2"/>
        <path d="M8 10V7a4 4 0 1 1 8 0v3"/>
    </svg>
@endsection

@section('heading', 'Akses Ditolak')
@section('description', 'Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator jika Anda merasa ini keliru.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
@endsection
