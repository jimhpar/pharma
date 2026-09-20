<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpandCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = [
            'Vitamins & Supplements',
            'Pain Relief',
            'Cold & Flu',
            'Digestive Care',
            'Diabetes Care',
            'Heart Health',
            'Skin Care',
            'Baby Care',
            'Women\'s Health',
            'Men\'s Health',
            'First Aid',
            'Oral Care',
            'Eye Care',
            'Respiratory Care',
            'Allergy Care',
            'Herbal Products',
            'Personal Care',
            'Medical Devices',
            'Protein & Nutrition',
            'Antibiotics',
            'Antacids',
            'Hormonal Care',
            'Bone & Joint Care',
            'Liver Care',
            'Kidney Care',
        ];

        $brandNames = [
            'Apex Pharma',
            'NovaCare',
            'MediPlus',
            'HealWell',
            'LifeSpring',
            'Zenith Labs',
            'CuraMed',
            'BioTrust',
            'Prime Health',
            'Nexa Pharma',
            'Wellmark',
            'Orion Remedies',
            'PureLife',
            'Guardian Pharma',
            'Evercare',
            'Sunrise Labs',
            'Vitalis',
            'HealthBridge',
            'Medistar',
            'CareNova',
            'GreenLeaf',
            'BioSphere',
            'TrustMed',
            'Optima Health',
            'Silverline',
            'Pulse Pharma',
            'BlueCross Labs',
            'PharmaCore',
            'RemedyX',
            'Unity Healthcare',
        ];

        $categories = collect($categoryNames)->map(function (string $name, int $index) {
            return Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_featured' => $index < 8,
                    'is_popular' => $index < 12,
                    'is_top_deal' => $index < 6,
                    'status' => 'active',
                ]
            );
        })->values();

        $brands = collect($brandNames)->map(function (string $name, int $index) {
            return Brand::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'rating' => round(3.8 + (($index % 12) * 0.1), 2),
                    'description' => $name . ' product line.',
                    'founded_year' => 1985 + ($index % 35),
                    'employees_count' => 120 + ($index * 17),
                    'is_verified' => true,
                    'status' => 'active',
                    'is_featured' => $index < 10,
                    'sort_order' => $index + 1,
                ]
            );
        })->values();

        $products = Product::query()->orderBy('id')->get();

        foreach ($products as $index => $product) {
            $primaryCategory = $categories[$index % $categories->count()];
            $secondaryCategory = $categories[($index + 7) % $categories->count()];
            $brand = $brands[$index % $brands->count()];

            $product->update([
                'category_id' => $primaryCategory->id,
                'brand_id' => $brand->id,
            ]);

            DB::table('product_categories')->updateOrInsert([
                'product_id' => $product->id,
                'category_id' => $primaryCategory->id,
            ]);

            if ($secondaryCategory->id !== $primaryCategory->id && $index % 3 === 0) {
                DB::table('product_categories')->updateOrInsert([
                    'product_id' => $product->id,
                    'category_id' => $secondaryCategory->id,
                ]);
            }
        }

        foreach ($brands as $brand) {
            $brand->update([
                'products_count' => Product::query()->where('brand_id', $brand->id)->count(),
            ]);
        }

        $this->command?->info(
            'Expanded catalog with ' . $categories->count() . ' categories and ' . $brands->count() . ' brands, then reassigned all products.'
        );
    }
}
