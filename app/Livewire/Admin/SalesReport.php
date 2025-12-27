<?php

namespace App\Livewire\Admin;

use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class SalesReport extends Component
{
    public string $period = 'today';
    public string $startDate;
    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->startDate = match($period) {
            'today' => now()->format('Y-m-d'),
            'yesterday' => now()->subDay()->format('Y-m-d'),
            'this_week' => now()->startOfWeek()->format('Y-m-d'),
            'this_month' => now()->startOfMonth()->format('Y-m-d'),
            default => $this->startDate,
        };
        $this->endDate = match($period) {
            'yesterday' => now()->subDay()->format('Y-m-d'),
            default => now()->format('Y-m-d'),
        };
    }

    public function render()
    {
        $reportService = new ReportService();
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $categoryReport = $reportService->getSalesByCategoryReport($start, $end);
        $paymentReport = $reportService->getPaymentMethodReport($start, $end);
        $topProducts = $reportService->getTopProductsReport($start, $end, 10);
        $dailySummary = $reportService->getDailySummaryReport($this->endDate);
        $peakHours = $reportService->getPeakHoursRangeReport($start, $end);

        return view('livewire.admin.sales-report', compact('categoryReport', 'paymentReport', 'topProducts', 'dailySummary', 'peakHours'))
            ->title('Laporan Penjualan');
    }
}
