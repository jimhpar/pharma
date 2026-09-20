<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RepairImportedCategories extends Command
{
    protected $signature = 'catalog:repair-imported-categories {--sleep=50}';

    protected $description = 'Re-read imported product breadcrumbs and remap them to a meaningful taxonomy level.';

    public function handle(): int
    {
        $updated = 0;

        Product::query()
            ->whereNotNull('source_external_id')
            ->orderBy('id')
            ->chunkById(100, function ($products) use (&$updated) {
                foreach ($products as $product) {
                    $html = $this->fetch('https://www.arogga.com/product/' . $product->source_external_id);
                    $categoryName = $this->extractCategoryName($html);

                    if (!$categoryName) {
                        continue;
                    }

                    $category = Category::query()->firstOrCreate(
                        ['slug' => Str::slug($categoryName)],
                        ['name' => $categoryName, 'status' => 'active', 'sort_order' => 0]
                    );

                    $product->update(['category_id' => $category->id]);
                    $product->categories()->sync([$category->id]);
                    $updated++;

                    usleep(max((int) $this->option('sleep'), 0) * 1000);
                }
            });

        $this->info("Repaired {$updated} imported product categories.");

        return self::SUCCESS;
    }

    private function extractCategoryName(string $html): ?string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $payload = json_decode($node->textContent, true);
            if (($payload['@type'] ?? null) !== 'BreadcrumbList') {
                continue;
            }

            $trail = collect($payload['itemListElement'] ?? [])
                ->pluck('name')
                ->filter()
                ->reject(fn ($name) => $name === 'Home')
                ->values();

            return $trail->count() >= 2 ? (string) $trail->get(1) : null;
        }

        return null;
    }

    private function fetch(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 CatalogImporter/1.0',
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new \RuntimeException('Unable to fetch URL');
        }

        return $body;
    }
}
