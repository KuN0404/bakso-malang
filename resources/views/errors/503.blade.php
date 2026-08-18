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

@section('title', 'Sedang Pemeliharaan')
@section('code', '503 &middot; Maintenance')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L2 19v3h3l7.1-7.1a4 4 0 0 0 5.6-5.6l-2.5 2.5-2-2 2.5-2.5z"/>
    </svg>
@endsection

@section('heading', 'Sedang Dalam Pemeliharaan')
@section('description', 'Sistem sedang dalam proses pemeliharaan terjadwal. Kami akan segera kembali online, mohon coba lagi sebentar lagi.')

@section('actions')
    <button type="button" onclick="location.reload()" class="btn btn-primary">Muat Ulang Halaman</button>
@endsection
