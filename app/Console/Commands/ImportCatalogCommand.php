<?php

namespace App\Console\Commands;

use App\Actions\ConvertImageToWebp;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('pradiksha:import-catalog')]
#[Description('Import the product catalog and logo from the live pradikshaherbal.co.in site')]
class ImportCatalogCommand extends Command
{
    private const BASE_URL = 'https://www.pradikshaherbal.co.in';

    private const LOGO_URL = 'https://5.imimg.com/data5/SELLER/Logo/2023/7/324895901/RS/VU/FW/11559484/logo1-120x120.jpg';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    public function handle(ConvertImageToWebp $convertImageToWebp): int
    {
        $this->importLogo($convertImageToWebp);
        $this->importProducts($convertImageToWebp);

        return self::SUCCESS;
    }

    private function importLogo(ConvertImageToWebp $convertImageToWebp): void
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(30)->retry(3, 1000)->get(self::LOGO_URL);
        } catch (\Throwable $e) {
            $this->warn("Failed to download the logo: {$e->getMessage()}");

            return;
        }

        if ($response->failed()) {
            $this->warn('Failed to download the logo.');

            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($tempPath, $response->body());

        if (! is_dir(public_path('images'))) {
            mkdir(public_path('images'), 0755, true);
        }

        try {
            $convertImageToWebp($tempPath, public_path('images/logo.webp'));
            $this->info('Logo saved to public/images/logo.webp');
        } catch (\Throwable $e) {
            $this->warn("Failed to convert the logo: {$e->getMessage()}");
        } finally {
            unlink($tempPath);
        }
    }

    private function importProducts(ConvertImageToWebp $convertImageToWebp): void
    {
        $home = $this->fetch('/');

        if ($home === null) {
            $this->error('Could not fetch the homepage. Aborting.');

            return;
        }

        $entries = $this->parseProductList($home);

        if ($entries->isEmpty()) {
            $this->error('Could not find the product list on the homepage. The site markup may have changed.');

            return;
        }

        Storage::disk('public')->makeDirectory('products');

        $imported = 0;

        foreach ($entries->groupBy('category_path') as $categoryPath => $categoryEntries) {
            $html = $this->fetch($categoryPath);

            if ($html === null) {
                continue;
            }

            $datarefByName = $this->extractDatarefEntries($html)
                ->keyBy(fn (array $entry) => Str::lower(trim($entry['prd_name'])));

            foreach ($categoryEntries as $entry) {
                $datarefEntry = $datarefByName->get(Str::lower(trim($entry['name'])));
                $slug = Str::slug($entry['name']);

                $imagePath = null;
                $imageUrl = $datarefEntry['img_path'] ?? $datarefEntry['img_path1'] ?? null;

                if (filled($imageUrl)) {
                    $imagePath = $this->downloadProductImage($convertImageToWebp, $imageUrl, $slug);
                }

                Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $entry['name'],
                        'category' => $this->categoryLabel($categoryPath),
                        'description' => $this->buildDescription($datarefEntry),
                        'image_path' => $imagePath,
                        'source_url' => self::BASE_URL.$categoryPath,
                    ],
                );

                $imported++;
                $this->line("Imported: {$entry['name']}");
            }

            usleep(300_000);
        }

        $this->info("Imported {$imported} products across {$entries->groupBy('category_path')->count()} categories.");
    }

    private function fetch(string $path): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(30)
                ->retry(3, 1000)
                ->get(self::BASE_URL.$path);
        } catch (\Throwable $e) {
            $this->warn("Failed to fetch {$path}: {$e->getMessage()}");

            return null;
        }

        if ($response->failed()) {
            $this->warn("Failed to fetch {$path} ({$response->status()}).");

            return null;
        }

        return $response->body();
    }

    /**
     * Parse the `prdlist` JS variable embedded on every page into a flat list
     * of [category_path, name] pairs covering the whole site catalog.
     *
     * @return Collection<int, array{category_path: string, name: string}>
     */
    private function parseProductList(string $html): Collection
    {
        if (! preg_match('/var\s+prdlist\s*=\s*\'(.*?)\'\s*;\s*var\s+root_path/s', $html, $match)) {
            return collect();
        }

        $listHtml = str_replace('\"', '"', $match[1]);

        preg_match_all(
            '/<li>\s*<a href="(\/[a-z0-9-]+\.html)#[a-z0-9-]+"\s*>([^<]+)<\/a>\s*<\/li>/i',
            $listHtml,
            $matches,
            PREG_SET_ORDER,
        );

        return collect($matches)->map(fn (array $m) => [
            'category_path' => $m[1],
            'name' => trim(html_entity_decode($m[2])),
        ]);
    }

    /**
     * Parse the `dataref` JS variable on a category page into its product entries
     * (name, image URL, and spec sheet used to build a description).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function extractDatarefEntries(string $html): Collection
    {
        if (! preg_match('/dataref\d+\s*=\s*eval\(/', $html, $match, PREG_OFFSET_CAPTURE)) {
            return collect();
        }

        $start = $match[0][1] + strlen($match[0][0]);
        $depth = 0;
        $end = null;

        for ($i = $start, $len = strlen($html); $i < $len; $i++) {
            if ($html[$i] === '[') {
                $depth++;
            } elseif ($html[$i] === ']') {
                $depth--;

                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        if ($end === null) {
            return collect();
        }

        $json = substr($html, $start, $end - $start + 1);
        $json = preg_replace('/,(\s*[\]}])/', '$1', $json);

        $decoded = json_decode($json, true);

        return collect(is_array($decoded) ? $decoded : []);
    }

    private function buildDescription(?array $datarefEntry): ?string
    {
        $specs = $datarefEntry['isq_det_form'] ?? [];

        if (empty($specs)) {
            return null;
        }

        return collect($specs)
            ->map(fn (array $spec) => trim(($spec['FK_IM_SPEC_MASTER_DESC'] ?? '').': '.($spec['SUPPLIER_RESPONSE_DETAIL'] ?? ''), ': ')
            )
            ->filter()
            ->implode('. ').'.';
    }

    private function categoryLabel(string $categoryPath): string
    {
        return Str::of($categoryPath)
            ->basename('.html')
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private function downloadProductImage(ConvertImageToWebp $convertImageToWebp, string $imageUrl, string $slug): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(30)->retry(3, 1000)->get($imageUrl);
        } catch (\Throwable $e) {
            $this->warn("Failed to download image for [{$slug}]: {$e->getMessage()}");

            return null;
        }

        if ($response->failed()) {
            $this->warn("Failed to download image for [{$slug}].");

            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'product');
        file_put_contents($tempPath, $response->body());

        $relativePath = "products/{$slug}.webp";

        try {
            $convertImageToWebp($tempPath, Storage::disk('public')->path($relativePath));
        } catch (\Throwable $e) {
            $this->warn("Failed to convert image for [{$slug}]: {$e->getMessage()}");
            unlink($tempPath);

            return null;
        }

        unlink($tempPath);

        return $relativePath;
    }
}
