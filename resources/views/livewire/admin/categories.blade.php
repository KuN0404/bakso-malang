<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Kategori</h1>
            <p class="text-gray-500">Kelola kategori produk</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Kategori
        </button>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kategori..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Icon</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Urutan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Target Dapur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="{{ $category->icon ?? 'folder' }}" class="w-5 h-5 text-primary-600"></i>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $category->name }}</p>
                            @if($category->description)
                                <p class="text-sm text-gray-500 truncate max-w-xs">{{ $category->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $category->products_count }} produk</td>
                        <td class="px-6 py-4 text-gray-600">{{ $category->sort_order }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $category->target_kitchen?->value === 'drink' ? 'bg-blue-100 text-blue-800' : ($category->target_kitchen?->value === 'none' ? 'bg-gray-100 text-gray-700' : 'bg-amber-100 text-amber-800') }}">
                                {{ $category->target_kitchen?->label() ?? 'Dapur Makanan' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($category->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i data-lucide="x-circle" class="w-3 h-3"></i> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $category->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button 
                                @click="$dispatch('confirm-action', {
                                    title: 'Hapus Kategori',
                                    message: 'Apakah Anda yakin ingin menghapus kategori {{ $category->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    action: { componentId: $wire.__instance.id, method: 'delete' },
                                    params: {{ $category->id }}
                                })"
                                class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="folder-open" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada kategori</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($categories->hasPages())
            <div class="px-6 py-4 border-t">{{ $categories->links() }}</div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Masukkan nama">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea wire:model="description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Deskripsi opsional"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        @php
                            $iconOptions = config('icons.category_icons', []);
                            $currentIconLabel = collect($iconOptions)->firstWhere('name', $icon ?? 'folder')['label'] ?? 'Lainnya';
                        @endphp
                        <div wire:ignore>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                            <div x-data="iconDropdown('{{ $icon ?? 'folder' }}', '{{ $currentIconLabel }}')" @click.away="open = false" class="relative">
                                <button 
                                    type="button" 
                                    @click="toggleDropdown()" 
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white flex items-center gap-3 justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <div x-ref="iconContainer" class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="{{ $icon ?? 'folder' }}" class="w-4 h-4 text-primary-600"></i>
                                        </div>
                                        <span class="text-gray-700" x-text="selectedLabel"></span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                
                                <!-- Dropdown - max 3 items visible -->
                                <div 
                                    x-show="open" 
                                    x-transition
                                    x-cloak
                                    class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                                    style="max-height: 156px; overflow-y: auto;"
                                >
                                    @foreach($iconOptions as $iconOpt)
                                        <button 
                                            type="button"
                                            @click="selectIcon('{{ $iconOpt['name'] }}', '{{ $iconOpt['label'] }}')"
                                            :class="selectedName === '{{ $iconOpt['name'] }}' ? 'bg-primary-50 text-primary-700' : 'hover:bg-gray-50'"
                                            class="w-full px-4 py-3 flex items-center gap-3 text-left border-b border-gray-100 last:border-0"
                                        >
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="selectedName === '{{ $iconOpt['name'] }}' ? 'bg-primary-100' : 'bg-gray-100'">
                                                <i data-lucide="{{ $iconOpt['name'] }}" class="w-4 h-4" :class="selectedName === '{{ $iconOpt['name'] }}' ? 'text-primary-600' : 'text-gray-600'"></i>
                                            </div>
                                            <span class="text-sm font-medium">{{ $iconOpt['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                            <input type="number" wire:model="sort_order" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 @error('sort_order') border-red-500 @enderror">
                            @error('sort_order') 
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @else
                                <p class="text-gray-500 text-xs mt-1">Urutan terakhir: {{ $this->lastSortOrder }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Dapur (KDS) *</label>
                        <select wire:model="target_kitchen" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                            @foreach(\App\Enums\KitchenTarget::options() as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('target_kitchen') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Initialize icons on page load
    lucide.createIcons();
    
    // Re-initialize on Livewire updates
    Livewire.hook('morph.updated', () => {
        setTimeout(() => lucide.createIcons(), 50);
    });
    
    // Re-initialize after component updates
    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            setTimeout(() => lucide.createIcons(), 100);
        });
    });
</script>
@endscript
