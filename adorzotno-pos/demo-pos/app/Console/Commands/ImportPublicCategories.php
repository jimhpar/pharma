<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportPublicCategories extends Command
{
    protected $signature = 'catalog:import-public-categories {--sleep=20}';

    protected $description = 'Import public categories with full parent-child hierarchy.';

    public function handle(): int
    {
        $categoryUrls = $this->discoverCategoryUrls();
        $this->info('Discovered ' . count($categoryUrls) . ' category URLs.');

        $importedPaths = 0;
        $failed = 0;

        foreach ($categoryUrls as $url) {
            try {
                $trail = $this->extractBreadcrumbTrail($this->fetch($url));
                if (empty($trail)) {
                    continue;
                }

                $this->upsertTrail($trail);
                $importedPaths++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn('Failed: ' . $url . ' :: ' . $e->getMessage());
            }

            usleep(max((int) $this->option('sleep'), 0) * 1000);
        }

        $this->info("Imported {$importedPaths} category paths; failed {$failed}.");

        return self::SUCCESS;
    }

    private function discoverCategoryUrls(): array
    {
        $root = simplexml_load_string($this->fetch('https://www.arogga.com/sitemap.xml'));
        $rootNs = $root->getNamespaces(true)[''] ?? null;
        $sitemaps = [];

        foreach ($root->children($rootNs)->sitemap ?? [] as $node) {
            $loc = (string) $node->loc;
            if ($loc !== '') {
                $sitemaps[] = $loc;
            }
        }

        $urls = [];
        foreach ($sitemaps as $sitemapUrl) {
            $xml = simplexml_load_string($this->fetch($sitemapUrl));
            $ns = $xml->getNamespaces(true)[''] ?? null;

            foreach ($xml->children($ns)->url ?? [] as $node) {
                $url = (string) $node->loc;
                if (str_contains($url, '/category/')) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function extractBreadcrumbTrail(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $payload = json_decode($node->textContent, true);
            if (($payload['@type'] ?? null) !== 'BreadcrumbList') {
                continue;
            }

            return collect($payload['itemListElement'] ?? [])
                ->pluck('name')
                ->filter()
                ->reject(fn ($name) => $name === 'Home')
                ->values()
                ->all();
        }

        return [];
    }

    private function upsertTrail(array $trail): void
    {
        $parentId = null;
        $path = [];

        foreach ($trail as $index => $name) {
            $path[] = $name;

            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug(implode('-', $path))],
                [
                    'parent_id' => $parentId,
                    'name' => $name,
                    'sort_order' => $index,
                    'status' => 'active',
                ]
            );

            if ((int) $category->parent_id !== (int) $parentId) {
                $category->update(['parent_id' => $parentId]);
            }

            $parentId = $category->id;
        }
    }

    private function fetch(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 CategoryImporter/1.0',
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new \RuntimeException('Unable to fetch URL');
        }

        return $body;
    }
}
