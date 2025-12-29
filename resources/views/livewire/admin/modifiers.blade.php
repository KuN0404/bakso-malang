<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Modifier</h1>
            <p class="text-gray-500">Kelola grup dan opsi modifier</p>
        </div>
        <button wire:click="createGroup" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Grup
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Groups List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Grup Modifier</h3></div>
            <div class="divide-y divide-gray-100">
                @forelse($groups as $group)
                    <div wire:click="selectGroup({{ $group->id }})" class="p-4 cursor-pointer hover:bg-gray-50 {{ $selectedGroupId === $group->id ? 'bg-primary-50 border-l-4 border-primary-500' : '' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-800">{{ $group->name }}</p>
                                <p class="text-xs text-gray-500">{{ $group->modifiers_count }} opsi · {{ $group->selection_type }}</p>
                            </div>
                            <div class="flex gap-1">
                                <button wire:click.stop="editGroup({{ $group->id }})" class="p-1.5 text-gray-400 hover:text-primary-600 rounded"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <button @click.stop="$dispatch('confirm-action', { title: 'Hapus Grup', message: 'Hapus grup {{ $group->name }}?', confirmText: 'Ya, Hapus', type: 'danger', action: { componentId: $wire.__instance.id, method: 'deleteGroup' }, params: {{ $group->id }} })" class="p-1.5 text-gray-400 hover:text-red-600 rounded"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">Belum ada grup</div>
                @endforelse
            </div>
        </div>

        <!-- Modifiers List -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">
                    @if($selectedGroup)
                        Opsi: {{ $selectedGroup->name }}
                    @else
                        Pilih Grup
                    @endif
                </h3>
                @if($selectedGroupId)
                    <button wire:click="createModifier" class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg flex items-center gap-1">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Tambah Opsi
                    </button>
                @endif
            </div>
            <div class="divide-y divide-gray-100">
                @if($selectedGroupId)
                    @forelse($modifiers as $mod)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-800">{{ $mod->name }}</p>
                                <p class="text-sm text-gray-500">
                                    @if($mod->price_adjustment > 0)
                                        +Rp {{ number_format($mod->price_adjustment, 0, ',', '.') }}
                                    @elseif($mod->price_adjustment < 0)
                                        -Rp {{ number_format(abs($mod->price_adjustment), 0, ',', '.') }}
                                    @else
                                        Gratis
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($mod->is_active)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Aktif</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">Nonaktif</span>
                                @endif
                                <button wire:click="editModifier({{ $mod->id }})" class="p-1.5 text-gray-400 hover:text-primary-600 rounded"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <button @click="$dispatch('confirm-action', { title: 'Hapus Modifier', message: 'Hapus modifier {{ $mod->name }}?', confirmText: 'Ya, Hapus', type: 'danger', action: { componentId: $wire.__instance.id, method: 'deleteModifier' }, params: {{ $mod->id }} })" class="p-1.5 text-gray-400 hover:text-red-600 rounded"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">Belum ada opsi di grup ini</div>
                    @endforelse
                @else
                    <div class="p-8 text-center text-gray-400">
                        <i data-lucide="mouse-pointer-click" class="w-12 h-12 mx-auto mb-3"></i>
                        <p>Klik grup di sebelah kiri untuk melihat opsi</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Group Modal -->
    @if($showGroupModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingGroupId ? 'Edit' : 'Tambah' }} Grup</h3>
                    <button wire:click="$set('showGroupModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <form wire:submit="saveGroup" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Grup *</label>
                        <input type="text" wire:model="groupName" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Level Pedas">
                        @error('groupName') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Seleksi</label>
                        <select wire:model="groupSelectionType" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                            <option value="single">Single (Pilih 1)</option>
                            <option value="multiple">Multiple (Pilih banyak)</option>
                        </select>
                    </div>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="groupIsRequired" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">Wajib Dipilih</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="groupIsActive" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">Aktif</span>
                        </label>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showGroupModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modifier Modal -->
    @if($showModifierModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingModifierId ? 'Edit' : 'Tambah' }} Opsi</h3>
                    <button wire:click="$set('showModifierModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <form wire:submit="saveModifier" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Opsi *</label>
                        <input type="text" wire:model="modifierName" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Level 1">
                        @error('modifierName') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div x-data="moneyInput({{ $priceAdjustment }}, 'priceAdjustment')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tambahan Harga</label>
                        <input 
                            type="text"
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @blur="syncToWire()"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                            placeholder="0"
                        >
                    </div>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model="modifierIsActive" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                        <span class="text-sm text-gray-700">Aktif</span>
                    </label>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showModifierModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
