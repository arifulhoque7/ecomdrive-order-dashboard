<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProductSeeder extends Seeder
{
    /**
     * Photography comes from Shopify Burst (burst.shopify.com), which licenses
     * its catalogue for free commercial use.
     */
    protected const string PHOTO_BASE = 'https://burst.shopifycdn.com/photos/';

    protected const string PHOTO_PARAMS = '?width=640&format=pjpg&exif=0&iptc=0';

    /**
     * The catalogue the storefront and the counter both sell from.
     *
     * @var array<int, array{sku: string, name: string, category: string, price_cents: int, photo: string}>
     */
    protected array $catalogue = [
        [
            'sku' => 'AUR-10010',
            'name' => 'Aurora Wireless Headphones',
            'category' => 'Audio',
            'price_cents' => 18_900,
            'photo' => 'black-headphones-closeup.jpg',
        ],
        [
            'sku' => 'ORB-10020',
            'name' => 'Orbit Bluetooth Speaker',
            'category' => 'Audio',
            'price_cents' => 11_400,
            'photo' => 'blue-waterproof-speaker.jpg',
        ],
        [
            'sku' => 'NIM-10030',
            'name' => 'Nimbus Mechanical Keyboard',
            'category' => 'Desk',
            'price_cents' => 14_500,
            'photo' => 'a-keyboard-and-tablet-on-a-black-surface.jpg',
        ],
        [
            'sku' => 'HAL-10040',
            'name' => 'Halo LED Desk Lamp',
            'category' => 'Desk',
            'price_cents' => 6_400,
            'photo' => 'a-laptop-and-tablet-beam-from-a-desktop-backlit-by-a-lamp.jpg',
        ],
        [
            'sku' => 'SLA-10050',
            'name' => 'Slate Standing Desk Mat',
            'category' => 'Desk',
            'price_cents' => 7_800,
            'photo' => 'an-overhead-of-a-clean-organized-desk.jpg',
        ],
        [
            'sku' => 'VER-10060',
            'name' => 'Vertex 4K Webcam',
            'category' => 'Video',
            'price_cents' => 12_900,
            'photo' => 'camera-phone-laptop-a-photographer-s-desk.jpg',
        ],
        [
            'sku' => 'DRI-10070',
            'name' => 'Drift Laptop Sleeve',
            'category' => 'Accessories',
            'price_cents' => 3_900,
            'photo' => 'business-woman-with-laptop.jpg',
        ],
        [
            'sku' => 'COB-10080',
            'name' => 'Cobalt USB-C Hub',
            'category' => 'Accessories',
            'price_cents' => 5_600,
            'photo' => 'computer-cables.jpg',
        ],
        [
            'sku' => 'EMB-10090',
            'name' => 'Ember Travel Mug',
            'category' => 'Accessories',
            'price_cents' => 4_200,
            'photo' => 'a-lonely-coffee-mug.jpg',
        ],
        [
            'sku' => 'PUL-10100',
            'name' => 'Pulse Fitness Band',
            'category' => 'Wearables',
            'price_cents' => 9_900,
            'photo' => 'a-smart-watch-showing-that-the-timer-is-complete.jpg',
        ],
        [
            'sku' => 'LUM-10110',
            'name' => 'Lumen 27" 4K Monitor',
            'category' => 'Desk',
            'price_cents' => 44900,
            'photo' => 'a-woman-immersed-in-her-laptop-and-monitor.jpg',
        ],
        [
            'sku' => 'GLI-10120',
            'name' => 'Glide Wireless Mouse',
            'category' => 'Desk',
            'price_cents' => 5900,
            'photo' => 'close-up-of-computer-mouse-in-action.jpg',
        ],
        [
            'sku' => 'POS-10130',
            'name' => 'Posture Task Chair',
            'category' => 'Desk',
            'price_cents' => 28900,
            'photo' => 'angled-view-of-clean-desk-and-chair-setup.jpg',
        ],
        [
            'sku' => 'PAG-10140',
            'name' => 'Pageturn Dot Notebook',
            'category' => 'Home',
            'price_cents' => 1800,
            'photo' => 'a-floral-notebook-on-a-green-table-with-bejewelled-pen.jpg',
        ],
        [
            'sku' => 'HAU-10150',
            'name' => 'Haul Everyday Backpack',
            'category' => 'Bags',
            'price_cents' => 12400,
            'photo' => 'backpack-in-black.jpg',
        ],
        [
            'sku' => 'QUE-10160',
            'name' => 'Quench Insulated Bottle',
            'category' => 'Fitness',
            'price_cents' => 3200,
            'photo' => 'a-person-smiles-holding-a-water-bottle-and-a-yoga-mat.jpg',
        ],
        [
            'sku' => 'SOL-10170',
            'name' => 'Solstice Polarised Sunglasses',
            'category' => 'Accessories',
            'price_cents' => 8600,
            'photo' => 'a-hand-reaches-for-black-sunglasses-sitting-on-a-case.jpg',
        ],
        [
            'sku' => 'WIC-10180',
            'name' => 'Wick Soy Candle Trio',
            'category' => 'Home',
            'price_cents' => 2900,
            'photo' => 'a-line-of-white-candle-table-centerpieces.jpg',
        ],
        [
            'sku' => 'FRO-10190',
            'name' => 'Frond Monstera Plant',
            'category' => 'Home',
            'price_cents' => 3400,
            'photo' => 'a-backlit-monstera-plant-leaf.jpg',
        ],
        [
            'sku' => 'ROA-10200',
            'name' => 'Roast Single-Origin Beans',
            'category' => 'Kitchen',
            'price_cents' => 1900,
            'photo' => 'coffee-beans-and-mug-flatlay.jpg',
        ],
        [
            'sku' => 'PIV-10210',
            'name' => 'Pivot Phone Tripod',
            'category' => 'Video',
            'price_cents' => 4400,
            'photo' => 'hand-holding-mobile-phone-and-tripod.jpg',
        ],
        [
            'sku' => 'SHE-10220',
            'name' => 'Shell Impact Phone Case',
            'category' => 'Mobile',
            'price_cents' => 2600,
            'photo' => 'blue-phone-case-and-eyeglasses-on-wooden-table.jpg',
        ],
        [
            'sku' => 'FLO-10230',
            'name' => 'Flow Studio Yoga Mat',
            'category' => 'Fitness',
            'price_cents' => 6800,
            'photo' => 'female-yoga-fashion-and-mat.jpg',
        ],
        [
            'sku' => 'TRE-10240',
            'name' => 'Tread Everyday Sneakers',
            'category' => 'Fitness',
            'price_cents' => 13900,
            'photo' => 'black-and-white-sneakers-against-purple-and-white.jpg',
        ],
    ];

    /**
     * The shelves the catalogue is arranged on.
     *
     * @var array<string, string>
     */
    protected array $categories = [
        'Audio' => 'Headphones, speakers and everything that makes noise.',
        'Desk' => 'Keyboards, lighting and desk surfaces.',
        'Video' => 'Cameras, webcams and capture gear.',
        'Accessories' => 'Cables, sleeves and small add-ons.',
        'Wearables' => 'Bands, watches and trackers worn all day.',
        'Mobile' => 'Cases, mounts and phone-sized kit.',
        'Fitness' => 'Mats, bottles and training gear.',
        'Home' => 'Candles, plants and things that make a room.',
        'Kitchen' => 'Coffee, mugs and counter-top staples.',
        'Bags' => 'Backpacks, sleeves and carry.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Collection::make($this->categories)
            ->map(fn (string $description, string $name) => Category::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            ));

        Collection::make($this->catalogue)->each(fn (array $product) => Product::query()->updateOrCreate(
            ['sku' => $product['sku']],
            [
                'name' => $product['name'],
                'category_id' => $categories->get($product['category'])->id,
                'price_cents' => $product['price_cents'],
                'image_url' => self::PHOTO_BASE.$product['photo'].self::PHOTO_PARAMS,
                'is_active' => true,
            ],
        ));
    }
}
