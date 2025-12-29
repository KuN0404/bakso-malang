<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Layout('layouts.admin')]
class Products extends Component
{
    use WithPagination, WithFileUploads;

    public bool $showModal = false;
    public bool $showViewModal = false;
    public ?int $editingId = null;
    public ?Product $selectedProduct = null;

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
    public bool $track_stock = false;
    public int $stock = 0;

    public array $selectedModifierGroups = [];

    public string $search = '';
    public ?int $filterCategory = null;

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
     * Convert and compress image to WebP format
     * Optimized for POS product thumbnails
     */
    protected function processImage($uploadedImage): ?string
    {
        if (!$uploadedImage) {
            return null;
        }

        // Generate unique filename
        $filename = 'products/' . Str::uuid() . '.webp';
        
        // Read and process image with Intervention Image
        $image = Image::read($uploadedImage->getRealPath());
        
        // Resize for product thumbnails (max 600px - optimal for POS display)
        $image->scaleDown(width: 600, height: 600);
        
        // Encode to WebP with 75% quality (sweet spot: size vs quality)
        // Results in ~20-50KB for most product photos
        $encoded = $image->toWebp(quality: 75);
        
        // Store the processed image
        Storage::disk('public')->put($filename, (string) $encoded);
        
        return $filename;
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
     * View product details
     */
    public function view(int $id): void
    {
        $this->selectedProduct = Product::with(['category', 'modifierGroups'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'price', 'cost_price', 'image', 'existingImage', 'is_active', 'is_featured', 'track_stock', 'stock', 'category_id', 'selectedModifierGroups']);
        $this->is_active = true;
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
            'track_stock' => $this->track_stock,
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
            $this->dispatch('notify', type: 'success', message: 'Produk berhasil ditambahkan');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'sku', 'description', 'price', 'cost_price', 'image', 'existingImage', 'is_active', 'is_featured', 'track_stock', 'stock', 'category_id', 'selectedModifierGroups']);
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

    public function render()
    {
        $products = Product::with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->latest()
            ->paginate(10);

        $categories = Category::active()->ordered()->get();
        $modifierGroups = ModifierGroup::active()->get();

        return view('livewire.admin.products', compact('products', 'categories', 'modifierGroups'))
            ->title('Produk');
    }
}
