<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BackfillProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $imagePaths = collect(File::files(public_path('productImage')))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true))
            ->map(fn ($file) => 'public/productImage/' . $file->getFilename())
            ->values();

        if ($imagePaths->isEmpty()) {
            $this->command?->error('No product image files found in public/productImage.');
            return;
        }

        $updatedThumbnails = 0;
        $createdGalleryImages = 0;

        Product::query()
            ->with(['sku:id,product_id'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($imagePaths, &$updatedThumbnails, &$createdGalleryImages) {
                foreach ($products as $index => $product) {
                    $imagePath = $imagePaths[($product->id + $index) % $imagePaths->count()];

                    if (empty($product->thumbnail_image)) {
                        $product->update(['thumbnail_image' => $imagePath]);
                        $updatedThumbnails++;
                    }

                    if (!ProductImage::query()->where('product_id', $product->id)->exists()) {
                        ProductImage::query()->create([
                            'product_id' => $product->id,
                            'sku_id' => $product->sku->first()?->id,
                            'image_path' => $imagePath,
                            'alt_text' => $product->name . ' Image',
                            'sort_order' => 0,
                        ]);
                        $createdGalleryImages++;
                    }
                }
            });

        $this->command?->info(
            "Backfilled {$updatedThumbnails} thumbnails and {$createdGalleryImages} gallery images."
        );
    }
}
