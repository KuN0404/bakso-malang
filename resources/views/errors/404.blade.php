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

@section('title', 'Halaman Tidak Ditemukan')
@section('code', '404 &middot; Not Found')

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="10" cy="10" r="6"/>
        <path d="M21 21l-4.3-4.3"/>
    </svg>
@endsection

@section('heading', 'Halaman Tidak Ditemukan')
@section('description', 'Halaman yang Anda cari tidak tersedia, sudah dipindahkan, atau alamatnya salah ketik.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
@endsection
