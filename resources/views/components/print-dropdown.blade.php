@props(['route', 'params' => []])

@php
    $url = route($route, $params);
    $separator = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
@endphp

<div class="flex items-center gap-1 bg-gray-800 rounded-xl p-1" x-data="{ format: 'A4' }">
    <!-- Select Format -->
    <div class="relative group">
        <select 
            x-model="format"
            class="bg-transparent text-white text-sm font-medium border-0 focus:ring-0 cursor-pointer pl-3 pr-7 py-1.5 appearance-none hover:bg-gray-700 rounded-lg transition-colors outline-none"
            title="Pilih Ukuran Kertas"
        >
            <option value="A4" class="text-gray-900 bg-white">Laporan (A4)</option>
            <option value="A5" class="text-gray-900 bg-white">Invoice (A5)</option>
            <option value="58mm" class="text-gray-900 bg-white">Struk (58mm)</option>
            <option value="80mm" class="text-gray-900 bg-white">Struk (80mm)</option>
        </select>
        <!-- Custom Arrow -->
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white/50 group-hover:text-white transition-colors">
            <i data-lucide="chevron-down" class="w-3 h-3"></i>
        </div>
    </div>
    
    <!-- Divider -->
    <div class="w-px h-5 bg-gray-600"></div>
    
    <!-- Print Action -->
    <a 
        :href="'{{ $url }}' + '{{ $separator }}format=' + format"
        target="_blank"
        class="flex items-center justify-center gap-2 px-3 py-1.5 text-white hover:bg-gray-700 rounded-lg transition-colors"
        title="Cetak Sekarang"
    >
        <i data-lucide="printer" class="w-4 h-4"></i>
        <span class="text-sm font-medium">Cetak</span>
    </a>
</div>
