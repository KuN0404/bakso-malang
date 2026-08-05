<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ModifierGroup;
use App\Models\Modifier;

class SampleProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            ['name' => 'Bakso', 'slug' => 'bakso', 'icon' => 'soup', 'sort_order' => 1, 'target_kitchen' => 'food'],
            ['name' => 'Mie', 'slug' => 'mie', 'icon' => 'utensils', 'sort_order' => 2, 'target_kitchen' => 'food'],
            ['name' => 'Minuman', 'slug' => 'minuman', 'icon' => 'cup-soda', 'sort_order' => 3, 'target_kitchen' => 'drink'],
            ['name' => 'Tambahan', 'slug' => 'tambahan', 'icon' => 'plus-circle', 'sort_order' => 4, 'target_kitchen' => 'food'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Modifier Groups
        $levelPedas = ModifierGroup::firstOrCreate(
            ['name' => 'Level Pedas'],
            [
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
            ]
        );

        $topping = ModifierGroup::firstOrCreate(
            ['name' => 'Topping'],
            [
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 5,
            ]
        );

        // Modifiers for Level Pedas
        $pedasModifiers = [
            ['name' => 'Tidak Pedas', 'price_adjustment' => 0, 'sort_order' => 1],
            ['name' => 'Pedas Sedikit', 'price_adjustment' => 0, 'sort_order' => 2],
            ['name' => 'Pedas Sedang', 'price_adjustment' => 0, 'sort_order' => 3],
            ['name' => 'Pedas Banget', 'price_adjustment' => 0, 'sort_order' => 4],
        ];

        foreach ($pedasModifiers as $mod) {
            Modifier::firstOrCreate(
                ['modifier_group_id' => $levelPedas->id, 'name' => $mod['name']],
                $mod + ['modifier_group_id' => $levelPedas->id]
            );
        }

        // Modifiers for Topping
        $toppingModifiers = [
            ['name' => 'Tahu Goreng', 'price_adjustment' => 2000, 'sort_order' => 1],
            ['name' => 'Siomay', 'price_adjustment' => 3000, 'sort_order' => 2],
            ['name' => 'Pangsit Goreng', 'price_adjustment' => 2500, 'sort_order' => 3],
            ['name' => 'Telur Puyuh', 'price_adjustment' => 3000, 'sort_order' => 4],
            ['name' => 'Kerupuk', 'price_adjustment' => 1000, 'sort_order' => 5],
        ];

        foreach ($toppingModifiers as $mod) {
            Modifier::firstOrCreate(
                ['modifier_group_id' => $topping->id, 'name' => $mod['name']],
                $mod + ['modifier_group_id' => $topping->id]
            );
        }

        // Products
        $baksoCategory = Category::where('slug', 'bakso')->first();
        $mieCategory = Category::where('slug', 'mie')->first();
        $minumanCategory = Category::where('slug', 'minuman')->first();
        $tambahanCategory = Category::where('slug', 'tambahan')->first();

        $products = [
            // Bakso
            ['category_id' => $baksoCategory->id, 'name' => 'Bakso Biasa', 'sku' => 'BSO-001', 'price' => 15000, 'cost_price' => 8000],
            ['category_id' => $baksoCategory->id, 'name' => 'Bakso Urat', 'sku' => 'BSO-002', 'price' => 18000, 'cost_price' => 10000],
            ['category_id' => $baksoCategory->id, 'name' => 'Bakso Jumbo', 'sku' => 'BSO-003', 'price' => 22000, 'cost_price' => 12000],
            ['category_id' => $baksoCategory->id, 'name' => 'Bakso Telur', 'sku' => 'BSO-004', 'price' => 20000, 'cost_price' => 11000],
            ['category_id' => $baksoCategory->id, 'name' => 'Bakso Gepeng', 'sku' => 'BSO-005', 'price' => 17000, 'cost_price' => 9000],
            ['category_id' => $baksoCategory->id, 'name' => 'Bakso Komplit', 'sku' => 'BSO-006', 'price' => 25000, 'cost_price' => 14000, 'is_featured' => true],
            
            // Mie
            ['category_id' => $mieCategory->id, 'name' => 'Mie Ayam', 'sku' => 'MIE-001', 'price' => 15000, 'cost_price' => 8000],
            ['category_id' => $mieCategory->id, 'name' => 'Mie Ayam Bakso', 'sku' => 'MIE-002', 'price' => 20000, 'cost_price' => 11000],
            ['category_id' => $mieCategory->id, 'name' => 'Mie Yamin', 'sku' => 'MIE-003', 'price' => 17000, 'cost_price' => 9000],
            
            // Minuman
            ['category_id' => $minumanCategory->id, 'name' => 'Es Teh Manis', 'sku' => 'MNM-001', 'price' => 5000, 'cost_price' => 2000],
            ['category_id' => $minumanCategory->id, 'name' => 'Es Jeruk', 'sku' => 'MNM-002', 'price' => 7000, 'cost_price' => 3000],
            ['category_id' => $minumanCategory->id, 'name' => 'Teh Hangat', 'sku' => 'MNM-003', 'price' => 4000, 'cost_price' => 1500],
            ['category_id' => $minumanCategory->id, 'name' => 'Air Mineral', 'sku' => 'MNM-004', 'price' => 5000, 'cost_price' => 2500],
            
            // Tambahan
            ['category_id' => $tambahanCategory->id, 'name' => 'Nasi Putih', 'sku' => 'TMB-001', 'price' => 5000, 'cost_price' => 3000],
            ['category_id' => $tambahanCategory->id, 'name' => 'Tahu Goreng', 'sku' => 'TMB-002', 'price' => 3000, 'cost_price' => 1500],
            ['category_id' => $tambahanCategory->id, 'name' => 'Pangsit Goreng', 'sku' => 'TMB-003', 'price' => 4000, 'cost_price' => 2000],
        ];

        foreach ($products as $prod) {
            $product = Product::firstOrCreate(
                ['sku' => $prod['sku']],
                $prod
            );

            // Attach modifier groups to bakso and mie products
            if (in_array($prod['category_id'], [$baksoCategory->id, $mieCategory->id])) {
                $product->modifierGroups()->syncWithoutDetaching([$levelPedas->id, $topping->id]);
            }
        }

        // Components (Item Setengah Jadi)
        $components = [
            ['code' => 'CMP-001', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 100, 'minimum_stock' => 20, 'cost_price' => 1500],
            ['code' => 'CMP-002', 'name' => 'Bakso Besar Urat', 'unit' => 'pcs', 'stock' => 50, 'minimum_stock' => 10, 'cost_price' => 4000],
            ['code' => 'CMP-003', 'name' => 'Kuah Kaldu', 'unit' => 'porsi', 'stock' => 80, 'minimum_stock' => 15, 'cost_price' => 1000],
            ['code' => 'CMP-004', 'name' => 'Tahu Goreng Komponen', 'unit' => 'pcs', 'stock' => 60, 'minimum_stock' => 10, 'cost_price' => 800],
        ];

        $createdComponents = [];
        foreach ($components as $c) {
            $createdComponents[$c['code']] = \App\Models\Component::firstOrCreate(['code' => $c['code']], $c);
        }

        // Link modifier "Tahu Goreng" ke komponen "Tahu Goreng Komponen"
        $tahuModifier = Modifier::where('name', 'Tahu Goreng')->first();
        if ($tahuModifier && isset($createdComponents['CMP-004'])) {
            $tahuModifier->update(['component_id' => $createdComponents['CMP-004']->id]);
        }

        // BOM for Products
        $baksoBiasa   = Product::where('sku', 'BSO-001')->first();
        $baksoUrat    = Product::where('sku', 'BSO-002')->first();
        $baksoKomplit = Product::where('sku', 'BSO-006')->first();

        if ($baksoBiasa && isset($createdComponents['CMP-001'], $createdComponents['CMP-003'])) {
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoBiasa->id, 'component_id' => $createdComponents['CMP-001']->id], ['quantity' => 3]);
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoBiasa->id, 'component_id' => $createdComponents['CMP-003']->id], ['quantity' => 1]);
        }

        if ($baksoUrat && isset($createdComponents['CMP-002'], $createdComponents['CMP-001'], $createdComponents['CMP-003'])) {
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoUrat->id, 'component_id' => $createdComponents['CMP-002']->id], ['quantity' => 1]);
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoUrat->id, 'component_id' => $createdComponents['CMP-001']->id], ['quantity' => 2]);
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoUrat->id, 'component_id' => $createdComponents['CMP-003']->id], ['quantity' => 1]);
        }

        if ($baksoKomplit && isset($createdComponents['CMP-002'], $createdComponents['CMP-001'], $createdComponents['CMP-003'])) {
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoKomplit->id, 'component_id' => $createdComponents['CMP-002']->id], ['quantity' => 1]);
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoKomplit->id, 'component_id' => $createdComponents['CMP-001']->id], ['quantity' => 3]);
            \App\Models\ProductBom::firstOrCreate(['product_id' => $baksoKomplit->id, 'component_id' => $createdComponents['CMP-003']->id], ['quantity' => 1]);
        }

        $this->command->info('Sample products & components seeded successfully!');
    }
}
