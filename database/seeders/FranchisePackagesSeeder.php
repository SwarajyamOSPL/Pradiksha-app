<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Creates the three franchise packages (Basic, Mini and Super) with their fixed prices, contents and extras,
 * adding the few products they need that are not in the catalogue yet.
 *
 * Safe to run more than once: existing products and packages are left exactly as they are, so edits
 * made in the admin are never overwritten.
 */
class FranchisePackagesSeeder extends Seeder
{
    /**
     * Products the packages need that may not exist yet. The price is the MRP from the package sheets.
     *
     * @var array<string, array{name: string, category: string, description: string, price: int, image: string|null}>
     */
    private const NEW_PRODUCTS = [
        'pradiksha-ortho-oil-50ml' => [
            'name' => 'Pradiksha Ortho Oil 50ml',
            'category' => 'Joint Pain Relief Oil',
            'description' => 'Form: Oil. Pack Size: 50 ml.',
            'price' => 130,
            'image' => 'ortho-oil-50ml.webp',
        ],
        'pradiksha-ortho-syrup-200ml' => [
            'name' => 'Pradiksha Ortho Syrup 200ml',
            'category' => 'Ayurvedic Joint Pain Syrup',
            'description' => 'Form: Syrup. Pack Size: 200 ml.',
            'price' => 250,
            'image' => null,
        ],
        'pradiksha-ortho-capsules-60-caps' => [
            'name' => 'Pradiksha Ortho Capsules 60 caps',
            'category' => 'Ayurvedic Tablets',
            'description' => 'Form: Capsules. Pack Size: 60 capsules.',
            'price' => 299,
            'image' => 'ortho-capsules-60.webp',
        ],
        'pradiksha-onion-hair-oil-200ml' => [
            'name' => 'Pradiksha Onion Hair Oil 200ml',
            'category' => 'Hair Oil',
            'description' => 'Form: Oil. Pack Size: 200 ml.',
            'price' => 299,
            'image' => 'onion-hair-oil-200ml.webp',
        ],
        'pradiksha-piles-care-capsules-30-caps' => [
            'name' => 'Pradiksha Piles Care Capsules 30 caps',
            'category' => 'Ayurvedic Tablets',
            'description' => 'Form: Capsules. Pack Size: 30 capsules.',
            'price' => 199,
            'image' => 'piles-care-capsules-30.webp',
        ],
    ];

    /**
     * Each package: its fixed price and the product slugs with quantities, in the order of the package sheets.
     *
     * @var array<string, array{name: string, price: int, items: array<string, int>}>
     */
    private const PACKAGES = [
        'basic' => [
            'name' => 'Basic',
            'price' => 5000,
            'items' => [
                'pradiksha-ortho-oil-50ml' => 5,
                'pradiksha-ortho-oil-100ml-joint-relief-oil' => 5,
                'pradiksha-ortho-syrup-200ml' => 5,
                'pradiksha-ortho-capsules-60-caps' => 3,
                'pradiksha-onion-hair-oil-200ml' => 3,
                'pradiksha-onion-shampoo-200ml' => 2,
                'sar-balm' => 11,
                'pradiksha-besto-slim-powder' => 2,
                'mushroom-ad-soup-powder-for-weight-gain' => 2,
                'pradiksha-diafit-powder' => 3,
                'pradiksha-piles-care-capsules-30-caps' => 3,
                'shila-shakti-capsule' => 2,
            ],
        ],
        'mini' => [
            'name' => 'Mini',
            'price' => 10000,
            'items' => [
                'pradiksha-ortho-oil-50ml' => 24,
                'pradiksha-ortho-oil-100ml-joint-relief-oil' => 24,
                'pradiksha-ortho-syrup-200ml' => 15,
                'pradiksha-ortho-capsules-60-caps' => 3,
                'pradiksha-onion-hair-oil-200ml' => 3,
                'pradiksha-onion-shampoo-200ml' => 3,
                'sar-balm' => 8,
                'pradiksha-besto-slim-powder' => 3,
                'mushroom-ad-soup-powder-for-weight-gain' => 3,
                'pradiksha-diafit-powder' => 3,
                'pradiksha-piles-care-capsules-30-caps' => 3,
                'shila-shakti-capsule' => 3,
            ],
        ],
        'super' => [
            'name' => 'Super',
            'price' => 25000,
            'items' => [
                'pradiksha-ortho-oil-50ml' => 48,
                'pradiksha-ortho-oil-100ml-joint-relief-oil' => 48,
                'pradiksha-ortho-syrup-200ml' => 48,
                'pradiksha-ortho-capsules-60-caps' => 5,
                'pradiksha-onion-hair-oil-200ml' => 12,
                'pradiksha-onion-shampoo-200ml' => 12,
                'sar-balm' => 12,
                'pradiksha-besto-slim-powder' => 12,
                'mushroom-ad-soup-powder-for-weight-gain' => 5,
                'pradiksha-diafit-powder' => 10,
                'pradiksha-piles-care-capsules-30-caps' => 5,
                'shila-shakti-capsule' => 13,
            ],
        ],
    ];

    /**
     * What each package promises besides its products, from the business offer poster: the worth of the
     * goods and the extras that come with it.
     *
     * @var array<string, array{goods_worth: int, inclusions: array<int, string>}>
     */
    private const EXTRAS = [
        'basic' => ['goods_worth' => 9000, 'inclusions' => ['Canopy', 'T Shirt', 'Visiting Card', 'I Card', 'Promotion Materials']],
        'mini' => ['goods_worth' => 20000, 'inclusions' => ['Canopy', 'T Shirt', 'Visiting Card', 'I Card', 'Promotion Materials']],
        'super' => ['goods_worth' => 55000, 'inclusions' => ['Canopy', 'T Shirt', 'Visiting Card', 'I Card', 'Promotion Materials (complete set)']],
    ];

    /**
     * The printed MRP of each product, from the package sheets.
     *
     * @var array<string, int>
     */
    private const MRP = [
        'pradiksha-ortho-oil-50ml' => 130,
        'pradiksha-ortho-oil-100ml-joint-relief-oil' => 250,
        'pradiksha-ortho-syrup-200ml' => 250,
        'pradiksha-ortho-capsules-60-caps' => 299,
        'pradiksha-onion-hair-oil-200ml' => 299,
        'pradiksha-onion-shampoo-200ml' => 299,
        'sar-balm' => 70,
        'pradiksha-besto-slim-powder' => 499,
        'mushroom-ad-soup-powder-for-weight-gain' => 399,
        'pradiksha-diafit-powder' => 250,
        'pradiksha-piles-care-capsules-30-caps' => 199,
        'shila-shakti-capsule' => 290,
    ];

    public function run(): void
    {
        $this->call(SarBalmProductSeeder::class);

        foreach (self::NEW_PRODUCTS as $slug => $details) {
            $this->createProduct($slug, $details);
        }

        // Fill in the MRP where the admin has not set one, never replacing an MRP that is already there.
        foreach (self::MRP as $slug => $mrp) {
            Product::query()->where('slug', $slug)->whereNull('mrp')->update(['mrp' => $mrp]);
        }

        $products = Product::query()
            ->whereIn('slug', collect(self::PACKAGES)->flatMap(fn (array $package) => array_keys($package['items']))->unique())
            ->get()
            ->keyBy('slug');

        foreach (self::PACKAGES as $slug => $details) {
            $missing = collect(array_keys($details['items']))->reject(fn (string $productSlug) => $products->has($productSlug));

            if ($missing->isNotEmpty()) {
                throw new RuntimeException("The {$details['name']} package needs products that are not in the catalogue: ".$missing->join(', '));
            }

            $package = Package::firstOrCreate(['slug' => $slug], [
                'name' => $details['name'],
                'fixed_price' => $details['price'],
                'price' => $details['price'],
                'subtotal' => $details['price'],
                'is_active' => true,
            ]);

            if ($package->wasRecentlyCreated) {
                $package->products()->sync(
                    collect($details['items'])->mapWithKeys(
                        fn (int $quantity, string $productSlug) => [$products[$productSlug]->id => ['quantity' => $quantity]],
                    )->all(),
                );

                $package->refreshPrice();
            }

            // Fill in the extras only where they were never set, so the admin's edits are kept.
            $package->forceFill([
                'goods_worth' => $package->goods_worth ?? self::EXTRAS[$slug]['goods_worth'],
                'inclusions' => $package->inclusions ?? self::EXTRAS[$slug]['inclusions'],
            ])->save();
        }
    }

    /**
     * @param  array{name: string, category: string, description: string, price: int, image: string|null}  $details
     */
    private function createProduct(string $slug, array $details): void
    {
        $imagePath = null;

        if ($details['image'] !== null) {
            $imagePath = 'products/'.$details['image'];
            $disk = Storage::disk('public');

            if (! $disk->exists($imagePath)) {
                $disk->put($imagePath, file_get_contents(database_path('seeders/assets/'.$details['image'])));
            }
        }

        Product::firstOrCreate(['slug' => $slug], [
            'name' => $details['name'],
            'category' => $details['category'],
            'description' => $details['description'],
            'image_path' => $imagePath,
            'price' => $details['price'],
            'stock' => 100,
            'is_active' => true,
        ]);
    }
}
