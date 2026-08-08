<?php

namespace App\Livewire\Admin;

use App\Models\WhatsappMessageLog;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman admin khusus WhatsApp (Fonnte): pairing QR, status koneksi device,
 * dan riwayat pengiriman. Dipisah dari Pengaturan umum supaya tidak menumpuk
 * jadi satu komponen Livewire raksasa.
 */
#[Layout('layouts.admin')]
class Whatsapp extends Component
{
    use WithPagination;

    public bool $fonnteConfigured = false;
    public ?string $waDeviceStatus = null;
    public ?string $waQrImage = null;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->fonnteConfigured = filled(config('services.fonnte.token'));
        if ($this->fonnteConfigured) {
            $this->checkWhatsappStatus();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Cek status koneksi device Fonnte. Di-cache singkat supaya tidak
     * memanggil API Fonnte di setiap render halaman. Webhook device-status
     * (kalau sudah didaftarkan di dashboard Fonnte) juga menulis ke cache
     * key yang sama, jadi status di sini bisa lebih segar dari 30 detik.
     */
    public function checkWhatsappStatus(): void
    {
        if (!$this->fonnteConfigured) {
            $this->waDeviceStatus = null;
            return;
        }

        $status = Cache::remember('fonnte_device_status', 30, function () {
            return app(FonnteService::class)->getDeviceStatus();
        });

        $this->waDeviceStatus = $status['device_status'] ?? 'disconnect';
    }

    /**
     * Tombol "Cek Status" manual — paksa lewati cache 30 detik.
     */
    public function refreshStatus(): void
    {
        $this->authorize('manage_whatsapp');

        Cache::forget('fonnte_device_status');
        $this->checkWhatsappStatus();
        $this->dispatch('notify', type: 'success', message: 'Status WhatsApp diperbarui');
    }

    /**
     * Minta QR baru dari Fonnte untuk dipindai lewat WhatsApp di HP.
     */
    public function connectWhatsapp(): void
    {
        $this->authorize('manage_whatsapp');

        if (!$this->fonnteConfigured) {
            $this->dispatch('notify', type: 'error', message: 'Token Fonnte belum diset di server (.env)');
            return;
        }

        $result = app(FonnteService::class)->getQrCode();

        if (!($result['status'] ?? false)) {
            $this->waQrImage = null;
            $reason = $result['reason'] ?? 'Gagal mengambil QR';
            if (str_contains(strtolower($reason), 'already connect')) {
                Cache::forget('fonnte_device_status');
                $this->checkWhatsappStatus();
                $this->dispatch('notify', type: 'success', message: 'WhatsApp sudah terhubung');
            } else {
                $this->dispatch('notify', type: 'error', message: $reason);
            }
            return;
        }

        $this->waQrImage = $result['url'] ?? null;
    }

    public function render()
    {
        $logs = WhatsappMessageLog::getPaginated($this->search, $this->statusFilter, 20);

        return view('livewire.admin.whatsapp', compact('logs'))
            ->title('WhatsApp');
    }
}
