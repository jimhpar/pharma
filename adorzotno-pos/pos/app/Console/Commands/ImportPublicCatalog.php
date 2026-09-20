<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Sku;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportPublicCatalog extends Command
{
    protected $signature = 'catalog:import-public
        {--limit= : Maximum number of product pages to import}
        {--offset=0 : Number of discovered product URLs to skip}
        {--sleep=150 : Delay between page requests in milliseconds}
        {--download-images : Download remote product images into public/productImage}
        {--urls-file= : Import only product URLs listed in this file, one per line}
        {--only-existing : Update matching imported products only; do not create new products}';

    protected $description = 'Import public product catalog data from sitemap-discovered product pages.';

    public function handle(): int
    {
        $this->warn('Arogga public catalog import is currently disabled.');
        $this->line('Re-enable this command intentionally before using it again.');

        return self::FAILURE;

        /*
        $urlsFile = $this->option('urls-file');
        $productUrls = $urlsFile
            ? $this->readProductUrlsFromFile($urlsFile)
            : $this->discoverProductUrls($this->discoverSitemapUrls());
        $offset = (int) $this->option('offset');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $targets = collect($productUrls)->slice($offset, $limit)->values();

        if ($limit !== null && $limit <= 20) {
            $this->line('Target URLs:');
            $targets->each(fn ($url) => $this->line($url));
        }

        $this->info('Discovered ' . count($productUrls) . ' product URLs. Importing ' . $targets->count() . ' pages...');

        $imported = 0;
        $failed = 0;

        foreach ($targets as $index => $url) {
            try {
                $parsed = $this->parseProductPage($url);
                if ($parsed === null) {
                    $failed++;
                    continue;
                }

                $this->upsertProduct($parsed);
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn('Failed: ' . $url . ' :: ' . $e->getMessage());
            }

            usleep(max((int) $this->option('sleep'), 0) * 1000);

            $processed = $index + 1;
            if ($processed % 100 === 0 || $processed === $targets->count()) {
                $this->info("Progress: {$processed}/{$targets->count()} processed; imported {$imported}; failed {$failed}.");
            }
        }

        $this->info("Imported {$imported} products; failed {$failed}.");

        return self::SUCCESS;
        */
    }

    private function readProductUrlsFromFile(string $path): array
    {
        if (!File::exists($path)) {
            throw new \RuntimeException("URL file not found: {$path}");
        }

        return collect(File::lines($path))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function discoverSitemapUrls(): array
    {
        $xml = simplexml_load_string($this->fetch('https://www.arogga.com/sitemap.xml'));
        $ns = $xml->getNamespaces(true)[''] ?? null;
        $sitemaps = [];

        foreach ($xml->children($ns)->sitemap ?? [] as $node) {
            $loc = (string) $node->loc;
            if ($loc !== '') {
                $sitemaps[] = $loc;
            }
        }

        return $sitemaps;
    }

    private function discoverProductUrls(array $sitemapUrls): array
    {
        $productUrls = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            $xml = simplexml_load_string($this->fetch($sitemapUrl));
            $ns = $xml->getNamespaces(true)[''] ?? null;

            foreach ($xml->children($ns)->url ?? [] as $node) {
                $url = (string) $node->loc;
                if (str_contains($url, '/product/')) {
                    $productUrls[] = $url;
                }
            }
        }

        return array_values(array_unique($productUrls));
    }

    private function parseProductPage(string $url): ?array
    {
        $html = $this->fetch($url);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $jsonLd = collect($xpath->query('//script[@type="application/ld+json"]'))
            ->map(fn ($node) => json_decode($node->textContent, true))
            ->first(fn ($payload) => is_array($payload) && ($payload['@type'] ?? null) === 'Product');

        if (!$jsonLd || empty($jsonLd['name'])) {
            return null;
        }

        $breadcrumbs = collect($xpath->query('//script[@type="application/ld+json"]'))
            ->map(fn ($node) => json_decode($node->textContent, true))
            ->first(fn ($payload) => is_array($payload) && ($payload['@type'] ?? null) === 'BreadcrumbList');

        $categoryTrail = collect($breadcrumbs['itemListElement'] ?? [])
            ->pluck('name')
            ->filter()
            ->reject(fn ($name) => in_array($name, ['Home'], true))
            ->values();

        $genericNode = collect($xpath->query('//p[contains(normalize-space(.), "Generic:")]'))
            ->first();
        $genericName = $genericNode
            ? trim(preg_replace('/^Generic:\s*/u', '', trim($genericNode->textContent)))
            : null;

        $additional = collect($jsonLd['additionalProperty'] ?? [])->mapWithKeys(
            fn ($item) => [($item['name'] ?? '') => $item['value'] ?? null]
        );

        $mrpPrice = $this->extractPriceFromNode($xpath, 'product_single_mrp_generate')
            ?? $this->extractPriceFromNode($xpath, 'product_single_price_generate')
            ?? (float) ($jsonLd['offers']['price'] ?? 0);
        $form = $additional->get('Form');

        return [
            'page_id' => $this->extractPageId($url),
            'legacy_external_id' => (string) ($jsonLd['sku'] ?? ''),
            'name' => trim((string) ($xpath->query('//h1')->item(0)?->textContent ?: $jsonLd['name'])),
            'slug' => Str::slug(parse_url($url, PHP_URL_PATH) ?: $jsonLd['name']),
            'generic_name' => $genericName,
            'manufacturer_name' => trim((string) ($jsonLd['brand']['name'] ?? '')),
            'brand_name' => trim((string) ($jsonLd['brand']['name'] ?? '')),
            'category_trail' => $this->resolveCategoryTrail($categoryTrail, $jsonLd['category'] ?? 'General'),
            'short_description' => $this->extractAboutThisItem($xpath),
            'long_description' => $this->extractMedicineOverview($xpath),
            'image_url' => $jsonLd['image'] ?? null,
            'sale_price' => $mrpPrice,
            'mrp_price' => $mrpPrice,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'form' => $form,
            'strength' => $additional->get('Strength'),
            'coating_type' => $this->extractCoatingType($form, $html),
            'sales_unit' => $additional->get('Sales Unit'),
        ];
    }

    private function upsertProduct(array $data): void
    {
        $brand = Brand::query()->firstOrCreate(
            ['slug' => Str::slug($data['brand_name'] ?: 'unknown-brand')],
            [
                'name' => $data['brand_name'] ?: 'Unknown Brand',
                'status' => 'active',
                'sort_order' => 0,
            ]
        );

        $categoryIds = [];
        $parentId = null;
        $path = [];

        foreach ($data['category_trail'] as $categoryName) {
            $path[] = $categoryName;
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug(implode('-', $path))],
                [
                    'parent_id' => $parentId,
                    'name' => $categoryName,
                    'status' => 'active',
                    'sort_order' => 0,
                ]
            );
            $parentId = $category->id;
            $categoryIds[] = $category->id;
        }

        $primaryCategoryId = end($categoryIds) ?: null;

        $imagePath = $this->resolveImagePath($data['image_url']);

        $product = Product::query()
            ->where('source_external_id', $data['page_id'])
            ->when(
                $data['legacy_external_id'] !== '',
                fn ($query) => $query->orWhere('source_external_id', $data['legacy_external_id'])
            )
            ->first();

        if (!$product && $this->option('only-existing')) {
            return;
        }

        $product ??= new Product();
        $product->fill([
            'source_external_id' => $data['page_id'],
            'category_id' => $primaryCategoryId,
            'brand_id' => $brand->id,
            'name' => $data['name'],
            'mrp' => $data['mrp_price'],
            'sale_price' => $data['sale_price'],
            'default_discount_type' => $data['discount_type'],
            'default_discount_value' => $data['discount_value'],
            'dosage_form' => $data['form'] ?: null,
            'strength' => $data['strength'] ?: null,
            'coating_type' => $data['coating_type'] ?: null,
            'generic_name' => $data['generic_name'] ?: null,
            'manufacturer_name' => $data['manufacturer_name'] ?: null,
            'slug' => Str::slug($data['name']) . '-' . $data['page_id'],
            'product_type' => Product::TYPE_STANDARD,
            'short_description' => $data['short_description'],
            'long_description' => $data['long_description'],
            'thumbnail_image' => $imagePath,
            'status' => 'active',
            'is_online_enabled' => true,
            'is_pos_enabled' => true,
        ]);
        $product->save();

        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }

        $sku = $product->sku()->first() ?? new Sku(['product_id' => $product->id]);
        $preferredSkuCode = $data['legacy_external_id'] ?: $data['page_id'];
        $safeSkuCode = $this->resolveSkuCode($preferredSkuCode, $data['page_id'], $sku->id);
        $sku->fill([
            'sku_code' => $sku->sku_code ?: $safeSkuCode,
            'cost_price' => 0,
            'retail_price' => $data['mrp_price'],
            'online_price' => $data['sale_price'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'discount_amount' => $data['discount_amount'],
            'units_per_strip' => 1,
            'track_stock' => true,
            'track_batch' => true,
            'track_expiry' => true,
            'track_serial' => false,
            'status' => 'active',
        ]);
        $sku->save();

        if ($imagePath) {
            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'sku_id' => $sku->id],
                [
                    'image_path' => $imagePath,
                    'alt_text' => $product->name . ' Image',
                    'sort_order' => 0,
                ]
            );
        }
    }

    private function resolveImagePath(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (!$this->option('download-images')) {
            return $url;
        }

        $contents = @file_get_contents($url);
        if ($contents === false) {
            return null;
        }

        $directory = public_path('productImage');
        File::ensureDirectoryExists($directory);
        $filename = sha1($url) . '.jpg';
        File::put($directory . DIRECTORY_SEPARATOR . $filename, $contents);

        return 'public/productImage/' . $filename;
    }

    private function sanitizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_ireplace('Arogga', '', $value);
        $value = preg_replace("/[ \t]+/u", ' ', $value);
        $value = preg_replace("/\n{3,}/u", "\n\n", $value);

        return trim($value);
    }

    private function extractPageId(string $url): string
    {
        if (preg_match('#/product/(\d+)#', $url, $match)) {
            return $match[1];
        }

        throw new \RuntimeException("Unable to determine product page ID from URL: {$url}");
    }

    private function extractPriceFromNode(\DOMXPath $xpath, string $className): ?float
    {
        $node = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')]")->item(0);
        if (!$node) {
            return null;
        }

        $text = preg_replace('/[^\d.]/u', '', $node->textContent);

        return $text === '' ? null : (float) $text;
    }

    private function extractCoatingType(?string $form, string $html): ?string
    {
        if ($form && preg_match('/\b(film[- ]coated|sugar[- ]coated|enteric[- ]coated)\b/i', $form, $match)) {
            return Str::headline(str_replace('-', ' ', strtolower($match[1])));
        }

        if (preg_match('/\b(film[- ]coated|sugar[- ]coated|enteric[- ]coated)\b/i', strip_tags($html), $match)) {
            return Str::headline(str_replace('-', ' ', strtolower($match[1])));
        }

        return null;
    }


    private function resolveCategoryTrail($categoryTrail, string $fallback): array
    {
        // Breadcrumbs end with the product itself. Keep the taxonomy chain before it.
        if ($categoryTrail->count() >= 2) {
            return $categoryTrail->slice(0, -1)->values()->all();
        }

        return [$fallback ?: 'General'];
    }

    private function extractAboutThisItem(\DOMXPath $xpath): ?string
    {
        $heading = collect($xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::div][translate(normalize-space(.), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "about this item"]'))
            ->first();

        if (!$heading) {
            return null;
        }

        $container = $heading->parentNode;
        if (!$container) {
            return null;
        }

        $html = $container->ownerDocument?->saveHTML($container);

        return $this->sanitizeRichHtml($html, ['About this item']);
    }

    private function extractMedicineOverview(\DOMXPath $xpath): ?string
    {
        $heading = collect($xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4][contains(normalize-space(.), "Medicine Overview of")]'))
            ->first();

        if (!$heading) {
            return null;
        }

        $section = $heading->parentNode?->parentNode;
        $html = $section ? $section->ownerDocument?->saveHTML($section) : null;

        return $this->sanitizeRichHtml($html, ['বাংলা']);
    }

    private function sanitizeRichHtml(?string $html, array $removeTexts = []): ?string
    {
        if (!$html) {
            return null;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//*[self::script or self::style or self::svg or self::img or self::a]') as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::a]') as $node) {
            $text = trim($node->textContent);
            if (
                $text !== ''
                && (
                    in_array($text, $removeTexts, true)
                    || str_starts_with($text, 'Medicine Overview of')
                )
            ) {
                $node->parentNode?->removeChild($node);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return null;
        }

        $clean = '';
        foreach ($body->childNodes as $child) {
            $clean .= $dom->saveHTML($child);
        }

        $clean = trim($clean);

        return $clean === '' ? null : $clean;
    }

    private function resolveSkuCode(string $preferredSkuCode, string $pageId, ?int $currentSkuId = null): string
    {
        $preferredSkuCode = trim($preferredSkuCode);
        $baseCode = $preferredSkuCode !== '' ? $preferredSkuCode : $pageId;

        $conflict = Sku::query()
            ->where('sku_code', $baseCode)
            ->when($currentSkuId, fn ($query) => $query->where('id', '!=', $currentSkuId))
            ->exists();

        if (!$conflict) {
            return $baseCode;
        }

        return $baseCode . '-' . $pageId;
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
