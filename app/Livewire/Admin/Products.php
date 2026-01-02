<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\StockLog;

#[Layout('layouts.admin')]
class Products extends Component
{
    use WithPagination, WithFileUploads;

    public function mount()
    {
        // Sanitize Page Parameter: If non-numeric or < 1, reset to 1
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }

        // Validate Filter Category Slug from URL
        if ($this->filterCategory) {
            $exists = Category::where('slug', $this->filterCategory)->exists();
            if (!$exists) {
                $this->filterCategory = ''; // Reset if invalid
            }
        }
    }

    public bool $showModal = false;
    public bool $showStockModal = false;
    public ?int $editingId = null;
    public ?Product $selectedProduct = null;

    // Stock management props
    public string $stockAdjustmentType = 'add'; // add, sub, set
    public float $stockAdjustmentAmount = 0;
    public ?string $stockNote = '';

    public ?int $category_id = null;
    public string $name = '';
    public string $sku = '';
    public ?string $description = '';
    public float $price = 0;
    public float $cost_price = 0;
    
    public $image;
    public ?string $existingImage = null;

    public bool $is_active = true;
    public bool $is_featured = false;
    public bool $is_unlimited = true; // Default unlimited
    public bool $track_stock = false; // Deprecated in UI binding, used for DB
    public int $stock = 0;

    public array $selectedModifierGroups = [];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public ?string $filterCategory = '';

    /**
     * Generate auto SKU based on timestamp and random string
     */
    public function generateSku(): string
    {
        $prefix = 'PRD';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}{$timestamp}{$random}";
    }

    /**
     * Delete old image from storage
     */
    protected function deleteOldImage(?string $imagePath): void
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Process image: Resize, Convert to WebP, and Save
     */
    protected function processImage($image): ?string
    {
        if (!$image) return null;

        $filename = md5($image->getClientOriginalName() . time()) . '.webp';
        $path = 'products/' . $filename;

        // Resize and encode
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $img = $manager->read($image->getRealPath());
        
        $img->scaleDown(width: 800); // 800px max width, keep aspect ratio
        
        // Save to storage
        Storage::disk('public')->put($path, (string) $img->toWebp(quality: 80));

        return $path;
    }

    public function openStockModal(int $id): void
    {
        $this->selectedProduct = Product::findOrFail($id);
        $this->stockAdjustmentType = 'add';
        $this->stockAdjustmentAmount = 0;
        $this->stockNote = '';
        $this->showStockModal = true;
    }

    public function saveStock(string $type, int $amount, ?string $note): void
    {
        $this->stockAdjustmentType = $type;
        $this->stockAdjustmentAmount = $amount;
        $this->stockNote = $note;

        $rules = [
            'stockAdjustmentAmount' => 'required|numeric|min:1',
        ];  

        if ($type !== 'add') {
            $rules['stockNote'] = 'required|string|max:255';
        } else {
            $rules['stockNote'] = 'nullable|string|max:255';
        }

        $validator = \Illuminate\Support\Facades\Validator::make([
            'stockAdjustmentAmount' => $amount,
            'stockNote' => $note,
        ], $rules, [
            'stockNote.required' => 'Catatan wajib diisi untuk pengurangan atau penyesuaian stok.',
            'stockAdjustmentAmount.min' => 'Jumlah penyesuaian minimal 1.',
            'stockAdjustmentAmount.required' => 'Jumlah tidak boleh kosong.',
        ]);

        if ($validator->fails()) {
            $this->dispatch('notify', type: 'error', message: $validator->errors()->first());
            return;
        }

        $product = $this->selectedProduct;
        $oldStock = $product->stock;
        $newStock = $oldStock;

        if ($type === 'add') {
            $newStock += $amount;
            $logType = 'Penambahan';
        } elseif ($type === 'sub') {
            $newStock -= $amount;
            $logType = 'Pengurangan';
            if ($newStock < 0) {
                $this->dispatch('notify', type: 'error', message: "Stok tidak cukup. Stok saat ini: $oldStock");
                return;
            }
        } else {
            $newStock = $amount;
            $logType = 'Penyesuaian';
        }

        $product->update(['stock' => $newStock]);
        
        // Ensure track_stock is enabled if stock > 0
        if ($newStock > 0 && !$product->track_stock) {
            $product->update(['track_stock' => true]);
        }

        StockLog::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'amount' => $amount,
            'final_stock' => $newStock,
            'note' => $note,
        ]);

        $this->showStockModal = false;
        $this->dispatch('notify', type: 'success', message: "Stok {$product->name} berhasil diperbarui ({$logType})");
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'price', 'cost_price', 'image', 'existingImage', 'is_active', 'is_featured', 'track_stock', 'stock', 'category_id', 'selectedModifierGroups', 'is_unlimited']);
        $this->is_active = true;
        $this->is_unlimited = true; // Default to unlimited
        // Auto-fill SKU
        $this->sku = $this->generateSku();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $product = Product::with('modifierGroups')->findOrFail($id);
        $this->editingId = $product->id;
        $this->category_id = $product->category_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description ?? '';
        $this->price = $product->price;
        $this->cost_price = $product->cost_price;
        $this->existingImage = $product->image;
        $this->is_active = $product->is_active;
        $this->is_featured = $product->is_featured;
        $this->track_stock = $product->track_stock;
        $this->is_unlimited = !$product->track_stock;
        // Don't auto-fill stock on edit to prevent overwrites
        $this->stock = $product->stock;
        $this->selectedModifierGroups = $product->modifierGroups->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        // Custom validation rules
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|min:2|max:150',
            'sku' => 'required|max:50|unique:products,sku' . ($this->editingId ? ",{$this->editingId}" : ''),
            'description' => 'nullable|max:500',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ];
        
        $messages = [
            'sku.unique' => 'SKU ini sudah digunakan oleh produk lain.',
            'sku.required' => 'SKU wajib diisi.',
            'image.mimes' => 'Format gambar harus PNG, JPG, JPEG, atau SVG.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
            'image.image' => 'File harus berupa gambar.',
        ];
        
        $this->validate($rules, $messages);

        $data = [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description ?: null,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'track_stock' => !$this->is_unlimited,
            'stock' => $this->stock,
        ];

        // Handle image upload
        if ($this->image) {
            // Process and convert to WebP
            $newImagePath = $this->processImage($this->image);
            
            if ($newImagePath) {
                // Delete old image if updating
                if ($this->editingId && $this->existingImage) {
                    $this->deleteOldImage($this->existingImage);
                }
                
                $data['image'] = $newImagePath;
            }
        }

        if ($this->editingId) {
            $product = Product::find($this->editingId);
            $product->update($data);
            $product->modifierGroups()->sync($this->selectedModifierGroups);
            $this->dispatch('notify', type: 'success', message: 'Produk berhasil diperbarui');
        } else {
            $product = Product::create($data);
            $product->modifierGroups()->sync($this->selectedModifierGroups);
            
            // Log initial stock if any
            if ($this->stock > 0) {
                StockLog::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => 'initial',
                    'amount' => $this->stock,
                    'final_stock' => $this->stock,
                    'note' => 'Stok awal saat pembuatan produk',
                ]);
            }

            $this->dispatch('notify', type: 'success', message: 'Produk berhasil ditambahkan');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'sku', 'description', 'price', 'cost_price', 'image', 'existingImage', 'is_active', 'is_featured', 'track_stock', 'stock', 'category_id', 'selectedModifierGroups', 'is_unlimited']);
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);
        
        // Delete image first
        $product->deleteImage();
        
        // Then delete product
        $product->delete();
        
        $this->dispatch('notify', type: 'success', message: 'Produk berhasil dihapus');
    }

    /**
     * Remove existing image without deleting product
     */
    public function removeImage(): void
    {
        if ($this->editingId && $this->existingImage) {
            $product = Product::find($this->editingId);
            if ($product) {
                $product->deleteImage();
                $product->update(['image' => null]);
                $this->existingImage = null;
                $this->dispatch('notify', type: 'success', message: 'Gambar berhasil dihapus');
            }
        }
    }

    #[Url(except: 10)]
    public int $perPage = 10;

    public function updatedPerPage($value)
    {
        if (!in_array($value, [10, 25, 50])) {
            $this->perPage = 10;
        }
    }

    public function render()
    {
        $products = Product::with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            // Filter by Category Slug
            ->when($this->filterCategory, fn($q) => $q->whereHas('category', fn($c) => $c->where('slug', $this->filterCategory)))
            ->latest()
            ->paginate($this->perPage);

        // Smart Redirect: If page > lastPage, redirect to lastPage
        if ($products->lastPage() < $this->getPage() && $products->lastPage() > 0) {
            $this->setPage($products->lastPage());
            // Re-query with correct page
            $products = Product::with('category')
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
                ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
                ->latest()
                ->paginate($this->perPage);
        }

        $categories = Category::active()->ordered()->get();
        $modifierGroups = ModifierGroup::active()->get();

        return view('livewire.admin.products', compact('products', 'categories', 'modifierGroups'))
            ->title('Produk');
    }
}
