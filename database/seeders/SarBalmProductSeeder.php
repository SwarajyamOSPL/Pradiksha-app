<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Adds the SAR Balm (20 g) product with its photo to the catalogue. Safe to run more than once:
 * an existing SAR Balm is left exactly as it is, so edits made in the admin are never overwritten.
 */
class SarBalmProductSeeder extends Seeder
{
    private const IMAGE_PATH = 'products/sar-balm.webp';

    public function run(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::IMAGE_PATH)) {
            $disk->put(self::IMAGE_PATH, file_get_contents(database_path('seeders/assets/sar-balm.webp')));
        }

        Product::firstOrCreate(['slug' => 'sar-balm'], [
            'name' => 'SAR Balm',
            'category' => 'Pain Relief',
            'description' => 'SAR Balm, the quick acting balm. An Ayurvedic proprietary medicine. Form: Balm. Pack Size: 20 g.',
            'image_path' => self::IMAGE_PATH,
            'price' => 95,
            'stock' => 100,
            'is_active' => true,
        ]);
    }
}
