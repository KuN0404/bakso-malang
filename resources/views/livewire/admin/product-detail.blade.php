<div>
    <!-- Breadcrumb & Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('admin.products.index') }}" wire:navigate class="hover:text-primary-600">Produk</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900">Detail</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $product->name }}</h1>
            <a href="{{ route('admin.products.index') }}" wire:navigate class="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Product Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <!-- Image -->
                    <div class="flex-none">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="w-40 h-40 object-cover rounded-xl border border-gray-200" alt="{{ $product->name }}">
                        @else
                            <div class="w-40 h-40 bg-primary-50 rounded-xl flex items-center justify-center border border-primary-100">
                                <i data-lucide="package" class="w-16 h-16 text-primary-600"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="flex-1 grid grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">SKU</p>
                            <p class="font-mono text-gray-700">{{ $product->sku }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">Kategori</p>
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                {{ $product->category->name }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">Harga Jual</p>
                            <p class="font-bold text-lg text-primary-600">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">Harga Modal</p>
                            @can('view_financial_reports')
                                <p class="text-gray-700">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</p>
                            @else
                                <p class="text-gray-400 italic text-sm">Disembunyikan</p>
                            @endcan
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase mb-1">Status</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1 {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                
                                @if($product->is_featured)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 inline-flex items-center gap-1">
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i> Unggulan
                                    </span>
                                @endif
                                
                                {{-- Stock Status --}}
                                @if(!$product->track_stock)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-600 inline-flex items-center gap-1">
                                        <i data-lucide="infinity" class="w-3 h-3"></i> Unlimited
                                    </span>
                                @else
                                    @if($product->stock > 0)
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            Stok: {{ $product->stock }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            Stok Habis
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($product->description)
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-xs text-gray-500 uppercase mb-2">Deskripsi</p>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ $product->description }}</p>
                    </div>
                @endif
                
                @if($product->modifierGroups->isNotEmpty())
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-xs text-gray-500 uppercase mb-3">Modifier Groups</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->modifierGroups as $mg)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm bg-gray-50 text-gray-700 border border-gray-200">
                                    <i data-lucide="layers" class="w-3.5 h-3.5 text-gray-400"></i>
                                    {{ $mg->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Stock History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h3 class="font-semibold text-gray-800">Riwayat Aktivitas & Stok</h3>
                </div>
                
                @php
                    // Merge StockLogs and Activities
                    $logs = collect();
                    
                    // Add Stock Logs
                    foreach($product->stockLogs as $log) {
                        $logs->push([
                            'timestamp' => $log->created_at,
                            'type' => 'stock',
                            'action' => $log->type, // add, sub, set, etc
                            'user' => $log->user->name ?? 'System',
                            'description' => $log->note ?? '-',
                            'amount' => $log->amount,
                            'final_stock' => $log->final_stock,
                            'original' => $log
                        ]);
                    }

                    // Add Activity Logs (Spatie)
                    foreach($product->activities as $activity) {
                        $logs->push([
                            'timestamp' => $activity->created_at,
                            'type' => 'activity',
                            'action' => $activity->description, // created, updated
                            'user' => $activity->causer->name ?? 'System',
                            'description' => $activity->description === 'created' ? 'Produk Dibuat' : 'Perubahan Data',
                            'changes' => $activity->properties['attributes'] ?? [],
                            'original' => $activity
                        ]);
                    }

                    // Sort by timestamp desc
                    $logs = $logs->sortByDesc('timestamp');

                    // Attribute label mapping
                    $labels = [
                        'name' => 'Nama Produk',
                        'price' => 'Harga',
                        'cost_price' => 'Harga Modal',
                        'stock' => 'Stok',
                        'track_stock' => 'Kelola Stok',
                        'is_active' => 'Status Aktif',
                        'is_featured' => 'Status Unggulan',
                        'sku' => 'SKU',
                        'description' => 'Deskripsi',
                        'category_id' => 'Kategori',
                    ];
                @endphp

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Jenis</th>
                                <th class="px-6 py-3 text-right">Perubahan</th>
                                <th class="px-6 py-3">Oleh</th>
                                <th class="px-6 py-3">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-600">
                                        {{ $log['timestamp']->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-6 py-3">
                                        @if($log['type'] === 'stock')
                                            @if($log['action'] === 'add')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                                    <i data-lucide="plus" class="w-3 h-3"></i> Tambah Stok
                                                </span>
                                            @elseif($log['action'] === 'sub')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                                    <i data-lucide="minus" class="w-3 h-3"></i> Kurang Stok
                                                </span>
                                            @elseif($log['action'] === 'sale')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                                    <i data-lucide="shopping-cart" class="w-3 h-3"></i> Terjual
                                                </span>
                                            @elseif($log['action'] === 'initial')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                    Stok Awal
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                                    Penyesuaian
                                                </span>
                                            @endif
                                        @else
                                            @if($log['action'] === 'created')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-700">
                                                    <i data-lucide="plus-circle" class="w-3 h-3"></i> Produk Dibuat
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                                    <i data-lucide="edit-3" class="w-3 h-3"></i> Edit Produk
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right font-medium">
                                        @if($log['type'] === 'stock')
                                            <span class="{{ ($log['action'] === 'add' || $log['action'] === 'return') ? 'text-green-600' : ($log['action'] === 'sub' || $log['action'] === 'sale' ? 'text-red-600' : 'text-blue-600') }}">
                                                {{ ($log['action'] === 'add' || $log['action'] === 'return') ? '+' : '' }}{{ number_format($log['amount'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-xs text-gray-400 block">Sisa: {{ number_format($log['final_stock'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ $log['user'] }}</td>
                                    <td class="px-6 py-3 text-gray-500 text-sm">
                                        @if($log['type'] === 'stock')
                                            {{ $log['description'] }}
                                        @else
                                            @if($log['action'] === 'created')
                                                <span class="text-gray-500">Produk baru ditambahkan</span>
                                            @else
                                                <ul class="list-disc list-inside text-xs space-y-0.5">
                                                    @foreach($log['changes'] as $key => $value)
                                                        @if($key !== 'updated_at' && $key !== 'slug')
                                                            <li>
                                                                <span class="font-medium text-gray-700">{{ $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}:</span> 
                                                                @if(is_bool($value) || $key === 'is_active' || $key === 'is_featured' || $key === 'track_stock')
                                                                    {{ $value ? 'Ya/Aktif' : 'Tidak/Nonaktif' }}
                                                                @elseif(is_array($value))
                                                                    {{ json_encode($value) }}
                                                                @elseif($key === 'price' || $key === 'cost_price')
                                                                    Rp {{ number_format((float)$value, 0, ',', '.') }}
                                                                @elseif($key === 'category_id')
                                                                     (ID: {{ $value }})
                                                                @else
                                                                    {{ \Illuminate\Support\Str::limit($value, 30) }}
                                                                @endif
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        Belum ada aktivitas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Sidebar (Direct Actions? Maybe simplified) -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
                <div class="space-y-3">
                    @can('edit_products')
                        <a href="{{ route('admin.products.index') }}?edit={{ $product->id }}" wire:navigate class="block w-full text-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Edit Produk
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    lucide.createIcons();
</script>
@endscript
