<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\EmailLog;
use App\Models\IslandSeries;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\ShopVisit;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('cafeisla.demo_mode')) {
            $this->command?->warn('Demo records require CAFE_ISLA_DEMO_MODE=true.');

            return;
        }

        $password = Hash::make('Isla2026!');

        // 1. Users (4 RBAC Demo Accounts + 2 Additional Customers)
        $admin = User::create([
            'name' => 'Camille Reyes',
            'email' => 'admin@cafeisla.ph',
            'phone' => '0917-555-0101',
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Mateo Santos',
            'email' => 'barista@cafeisla.ph',
            'phone' => '0917-555-0102',
            'password' => $password,
            'role' => 'staff',
            'is_active' => true,
        ]);

        $courier = User::create([
            'name' => 'Jomar Dela Cruz',
            'email' => 'rider@cafeisla.ph',
            'phone' => '0917-555-0103',
            'password' => $password,
            'role' => 'courier',
            'is_active' => true,
        ]);

        $customer = User::create([
            'name' => 'Aria Mendoza',
            'email' => 'customer@cafeisla.ph',
            'phone' => '0917-555-0188',
            'password' => $password,
            'role' => 'customer',
            'is_active' => true,
        ]);

        $customerTwo = User::create([
            'name' => 'Rafael Villanueva',
            'email' => 'rafael@isla.ph',
            'phone' => '0918-442-0192',
            'password' => $password,
            'role' => 'customer',
            'is_active' => true,
        ]);

        $customerThree = User::create([
            'name' => 'Bianca Soriano',
            'email' => 'bianca@isla.ph',
            'phone' => '0927-884-0133',
            'password' => $password,
            'role' => 'customer',
            'is_active' => true,
        ]);

        // 2. Saved Customer Addresses
        UserAddress::create([
            'user_id' => $customer->id,
            'label' => 'San Juan Surf House',
            'recipient_name' => 'Aria Mendoza',
            'phone' => '0917-555-0188',
            'street_address' => '18 Urbiztondo Coastal Lane',
            'barangay' => 'Urbiztondo',
            'city' => 'San Juan',
            'province' => 'La Union',
            'postal_code' => '2514',
            'delivery_zone' => 'local_express',
            'is_default' => true,
        ]);

        UserAddress::create([
            'user_id' => $customer->id,
            'label' => 'Cebu Studio',
            'recipient_name' => 'Aria Mendoza',
            'phone' => '0917-555-0188',
            'street_address' => '42 Acacia Terraces, Lahug',
            'barangay' => 'Lahug',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'postal_code' => '6000',
            'delivery_zone' => 'provincial_parcel',
            'is_default' => false,
        ]);

        UserAddress::create([
            'user_id' => $customerTwo->id,
            'label' => 'Dasmariñas Residence',
            'recipient_name' => 'Rafael Villanueva',
            'phone' => '0918-442-0192',
            'street_address' => '104 Sampaloc II Avenue',
            'barangay' => 'Burol Main',
            'city' => 'Dasmariñas',
            'province' => 'Cavite',
            'postal_code' => '4114',
            'delivery_zone' => 'local_express',
            'is_default' => true,
        ]);

        // 3. Island Series (4 Philippine Coastal Chapters)
        $laUnion = IslandSeries::create([
            'code' => 'ISLA-LU',
            'number_label' => '01',
            'name' => 'La Union Series',
            'slug' => 'la-union',
            'subtitle' => 'San Juan Ceremonial Matcha & Hojicha Bar',
            'coordinates' => '16.6631° N, 120.3208° E',
            'terroir_story' => 'Inspired by calm dawn paddles off San Juan Point—hand-whisked first-harvest Uji ceremonial matcha and slow-roasted hojicha balanced with creamy oat milk, coconut water, and sea-salt foam.',
            'accent_hex' => '#4A6B46',
            'hero_image' => 'assets/images/editorial/series-la-union.jpg',
            'sort_order' => 1,
        ]);

        $siargao = IslandSeries::create([
            'code' => 'ISLA-SRG',
            'number_label' => '02',
            'name' => 'Siargao Series',
            'slug' => 'siargao',
            'subtitle' => 'Cloud 9 Dawn Patrol Espresso & Coconut Cold Brews',
            'coordinates' => '9.8482° N, 126.0458° E',
            'terroir_story' => 'Roasted Mount Apo beans and heirloom Mindanao tablea paired with fresh Pacific calamansi, muscovado, and young coconut cold brews built for reef mornings.',
            'accent_hex' => '#1F4E52',
            'hero_image' => 'assets/images/editorial/series-siargao.jpg',
            'sort_order' => 2,
        ]);

        $boracay = IslandSeries::create([
            'code' => 'ISLA-BRC',
            'number_label' => '03',
            'name' => 'Boracay Series',
            'slug' => 'boracay',
            'subtitle' => 'White Beach Golden Hour Crème & Sunset Coolers',
            'coordinates' => '11.9674° N, 121.9248° E',
            'terroir_story' => 'Velvety purple yam halaya, fragrant slow-simmered pandan, Guimaras mango purée, and bold Batangas barako spun into late-afternoon coastal treats.',
            'accent_hex' => '#B87B28',
            'hero_image' => 'assets/images/editorial/series-boracay.jpg',
            'sort_order' => 3,
        ]);

        $elNido = IslandSeries::create([
            'code' => 'ISLA-ELN',
            'number_label' => '04',
            'name' => 'El Nido Series',
            'slug' => 'el-nido',
            'subtitle' => 'Big Lagoon Botanical Teas & Wellness Elixirs',
            'coordinates' => '11.1956° N, 119.4180° E',
            'terroir_story' => 'Caffeine-light infusions of blue ternate butterfly pea, ruby hibiscus ginger, wild Palawan honey turmeric, and chilled silver needle white tea.',
            'accent_hex' => '#335C6E',
            'hero_image' => 'assets/images/editorial/series-elnido.jpg',
            'sort_order' => 4,
        ]);

        // 4. Categories
        $catMatcha = Category::create([
            'name' => 'Matcha & Roasted Tea',
            'slug' => 'matcha-roasted-tea',
            'product_type' => 'drink',
            'description' => 'Hand-whisked ceremonial matcha and roasted hojicha pours over oat milk and coconut water.',
            'sort_order' => 1,
        ]);

        $catEspresso = Category::create([
            'name' => 'Espresso & Coco Cold Brew',
            'slug' => 'espresso-coco-cold-brew',
            'product_type' => 'drink',
            'description' => 'Philippine single-origin espresso, citrus tonics, and slow-steeped coconut cold brews.',
            'sort_order' => 2,
        ]);

        $catCreme = Category::create([
            'name' => 'Golden Hour Crème & Coolers',
            'slug' => 'golden-hour-creme-coolers',
            'product_type' => 'drink',
            'description' => 'Signature island dessert lattes and sunlit tropical fruit coolers.',
            'sort_order' => 3,
        ]);

        $catBotanical = Category::create([
            'name' => 'Botanical Wellness Teas',
            'slug' => 'botanical-wellness-teas',
            'product_type' => 'drink',
            'description' => 'Bright floral, ginger, and turmeric herbal infusions made for slow afternoon recovery.',
            'sort_order' => 4,
        ]);

        $catMerch = Category::create([
            'name' => 'Isla Coastal Merch',
            'slug' => 'isla-coastal-merch',
            'product_type' => 'merch',
            'description' => 'Curated coastal linen, woven pandan goods, and resort essentials carried forward from Suot-Isla.',
            'sort_order' => 5,
        ]);

        // 5. Products & Variants (17 Drinks + 8 Isla Merch = 25 Products)
        $catalog = [
            // --- LA UNION SERIES (5 SIGNATURE DRINKS) ---
            [
                'island' => $laUnion->id,
                'category' => $catMatcha->id,
                'sku' => 'CI-LU-001',
                'name' => 'Urbiztondo Morning Matcha',
                'slug' => 'urbiztondo-morning-matcha',
                'short_excerpt' => 'Ceremonial matcha, oat milk, and in-house sweetener blend.',
                'description' => 'Our quiet morning house classic from the San Juan bar: stone-ground ceremonial-grade Uji matcha whisked to order, poured with silky barista oat milk and our light in-house cane sweetener blend.',
                'tasting_notes' => 'Sweet umami · toasted oat · velvety finish',
                'is_perishable' => true,
                'base_price' => 185,
                'image_path' => 'assets/images/drinks/urbiztondo-morning-matcha.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-LU-001-HOT', 'label' => 'Hot 12oz', 'serve_temp' => 'hot', 'size_option' => '12oz', 'price' => 185, 'stock_qty' => 28, 'threshold' => 5],
                    ['code' => 'CI-LU-001-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 195, 'stock_qty' => 32, 'threshold' => 5],
                ],
            ],
            [
                'island' => $laUnion->id,
                'category' => $catMatcha->id,
                'sku' => 'CI-LU-002',
                'name' => 'Monaliza Caramel Matcha',
                'slug' => 'monaliza-caramel-matcha',
                'short_excerpt' => 'Ceremonial-grade matcha, oat milk, sea salt foam, and in-house caramel blend.',
                'description' => 'Rich golden house caramel lined along chilled glass, creamy oat milk, vibrant ceremonial-grade matcha, and a thick whipped Pangasinan sea-salt cream cap.',
                'tasting_notes' => 'Burnt caramel · coastal sea salt · deep jade matcha',
                'is_perishable' => true,
                'base_price' => 205,
                'image_path' => 'assets/images/drinks/monaliza-caramel-matcha.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-LU-002-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 205, 'stock_qty' => 24, 'threshold' => 5],
                    ['code' => 'CI-LU-002-LRG', 'label' => 'Iced Large 20oz', 'serve_temp' => 'iced', 'size_option' => '20oz', 'price' => 225, 'stock_qty' => 3, 'threshold' => 5],
                ],
            ],
            [
                'island' => $laUnion->id,
                'category' => $catMatcha->id,
                'sku' => 'CI-LU-003',
                'name' => 'Carille Coco Matcha',
                'slug' => 'carille-coco-matcha',
                'short_excerpt' => 'Ceremonial matcha foam over coconut water with coconut shavings.',
                'description' => 'Light, crisp hydration after noon surf: fresh chilled coconut water over crystal ice crowned with dense ceremonial matcha cloud foam and toasted coconut shavings.',
                'tasting_notes' => 'Fresh bukó · creamy matcha cloud · crisp finish',
                'is_perishable' => true,
                'base_price' => 195,
                'image_path' => 'assets/images/drinks/carille-coco-matcha.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-LU-003-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 195, 'stock_qty' => 22, 'threshold' => 5],
                    ['code' => 'CI-LU-003-LRG', 'label' => 'Iced Large 20oz', 'serve_temp' => 'iced', 'size_option' => '20oz', 'price' => 215, 'stock_qty' => 14, 'threshold' => 5],
                ],
            ],
            [
                'island' => $laUnion->id,
                'category' => $catMatcha->id,
                'sku' => 'CI-LU-004',
                'name' => 'Tangadan Berry Matcha',
                'slug' => 'tangadan-berry-matcha',
                'short_excerpt' => 'Ceremonial-grade matcha, oat milk, and in-house strawberry blend.',
                'description' => 'Slow-macerated Benguet strawberry compote layered beneath cold oat milk and freshly whisked ceremonial-grade matcha—bright fruit sweetness meeting grassy earthiness.',
                'tasting_notes' => 'Crushed strawberry · velvety oat · floral matcha',
                'is_perishable' => true,
                'base_price' => 205,
                'image_path' => 'assets/images/drinks/tangadan-berry-matcha.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-LU-004-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 205, 'stock_qty' => 18, 'threshold' => 5],
                    ['code' => 'CI-LU-004-LRG', 'label' => 'Iced Large 20oz', 'serve_temp' => 'iced', 'size_option' => '20oz', 'price' => 225, 'stock_qty' => 10, 'threshold' => 5],
                ],
            ],
            [
                'island' => $laUnion->id,
                'category' => $catMatcha->id,
                'sku' => 'CI-LU-005',
                'name' => 'San Juan Spanish Hojicha',
                'slug' => 'san-juan-spanish-hojicha',
                'short_excerpt' => 'Hojicha, oat milk, and in-house condensed milk blend.',
                'description' => 'Low-caffeine charcoal-roasted green tea leaves whisked with oat milk and our house condensed milk blend for warm nutty comfort hot or iced.',
                'tasting_notes' => 'Roasted chestnut · cocoa husk · sweet cream',
                'is_perishable' => true,
                'base_price' => 190,
                'image_path' => 'assets/images/drinks/san-juan-spanish-hojicha.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-LU-005-HOT', 'label' => 'Hot 12oz', 'serve_temp' => 'hot', 'size_option' => '12oz', 'price' => 190, 'stock_qty' => 20, 'threshold' => 5],
                    ['code' => 'CI-LU-005-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 200, 'stock_qty' => 25, 'threshold' => 5],
                ],
            ],

            // --- SIARGAO SERIES (4 DRINKS) ---
            [
                'island' => $siargao->id,
                'category' => $catEspresso->id,
                'sku' => 'CI-SRG-006',
                'name' => 'Cloud 9 Sea Salt Latte',
                'slug' => 'cloud-9-sea-salt-latte',
                'short_excerpt' => 'Mount Apo double espresso, oat milk, muscovado, and whipped Pacific sea-salt cream.',
                'description' => 'Built for dawn surf checks: bold Mindanao double espresso sweetened lightly with unrefined muscovado over oat milk and salted cream microfoam.',
                'tasting_notes' => 'Dark muscovado · toasted hazelnut · sea spray',
                'is_perishable' => true,
                'base_price' => 190,
                'image_path' => 'assets/images/drinks/cloud-9-sea-salt-latte.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-SRG-006-HOT', 'label' => 'Hot 12oz', 'serve_temp' => 'hot', 'size_option' => '12oz', 'price' => 190, 'stock_qty' => 16, 'threshold' => 5],
                    ['code' => 'CI-SRG-006-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 200, 'stock_qty' => 26, 'threshold' => 5],
                ],
            ],
            [
                'island' => $siargao->id,
                'category' => $catEspresso->id,
                'sku' => 'CI-SRG-007',
                'name' => 'Kalamansi Espresso Tonic',
                'slug' => 'kalamansi-espresso-tonic',
                'short_excerpt' => 'Single-origin espresso floated over botanical tonic water, fresh Siargao calamansi, and rosemary.',
                'description' => 'Effervescent midday clarity: bright washed-process espresso suspended over sparkling quinine tonic pressed with whole calamansi and aromatic rosemary.',
                'tasting_notes' => 'Sparkling citrus · crisp botanicals · cacao crema',
                'is_perishable' => true,
                'base_price' => 185,
                'image_path' => 'assets/images/drinks/kalamansi-espresso-tonic.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-SRG-007-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 185, 'stock_qty' => 19, 'threshold' => 5],
                ],
            ],
            [
                'island' => $siargao->id,
                'category' => $catEspresso->id,
                'sku' => 'CI-SRG-008',
                'name' => 'Catangnan Coco Cold Brew',
                'slug' => 'catangnan-coco-cold-brew',
                'short_excerpt' => '18-hour slow cold brew over chilled young bukó water topped with toasted latik cream.',
                'description' => 'Silky dark-roast cold brew concentrate poured directly onto fresh island coconut water and finished with coconut-caramel latik cream.',
                'tasting_notes' => 'Toasted coconut · dark chocolate · clean finish',
                'is_perishable' => true,
                'base_price' => 195,
                'image_path' => 'assets/images/drinks/catangnan-coco-cold-brew.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-SRG-008-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 195, 'stock_qty' => 21, 'threshold' => 5],
                ],
            ],
            [
                'island' => $siargao->id,
                'category' => $catEspresso->id,
                'sku' => 'CI-SRG-009',
                'name' => 'Tablea Cacao Mocha',
                'slug' => 'tablea-cacao-mocha',
                'short_excerpt' => 'Heirloom Davao cacao tablea whisked with espresso, oat milk, and coconut blossom sugar.',
                'description' => 'Stone-ground Philippine cacao tablets melted hot into double espresso and velvety oat milk, dusted with roasted cacao nibs.',
                'tasting_notes' => 'Bittersweet tablea · roasted nib · warm spice',
                'is_perishable' => true,
                'base_price' => 195,
                'image_path' => 'assets/images/drinks/tablea-cacao-mocha.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-SRG-009-HOT', 'label' => 'Hot 12oz', 'serve_temp' => 'hot', 'size_option' => '12oz', 'price' => 195, 'stock_qty' => 18, 'threshold' => 5],
                    ['code' => 'CI-SRG-009-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 205, 'stock_qty' => 16, 'threshold' => 5],
                ],
            ],

            // --- BORACAY SERIES (4 DRINKS) ---
            [
                'island' => $boracay->id,
                'category' => $catCreme->id,
                'sku' => 'CI-BRC-010',
                'name' => 'Halaya Dirty Ube Latte',
                'slug' => 'halaya-dirty-ube-latte',
                'short_excerpt' => 'House-cooked purple yam halaya layered with oat milk and optional espresso ristretto.',
                'description' => 'Inspired by Station 1 golden-hour desserts—slow-simmered coconut ube halaya ribboned inside creamy oat milk with your choice of espresso kick or caffeine-free crème.',
                'tasting_notes' => 'Creamy purple yam · vanilla bean · bright espresso',
                'is_perishable' => true,
                'base_price' => 190,
                'image_path' => 'assets/images/drinks/halaya-dirty-ube-latte.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-BRC-010-DIRTY', 'label' => 'Iced 16oz (Dirty Espresso)', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 205, 'stock_qty' => 4, 'threshold' => 5],
                    ['code' => 'CI-BRC-010-CREME', 'label' => 'Iced 16oz (Caffeine-Free)', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 190, 'stock_qty' => 15, 'threshold' => 5],
                ],
            ],
            [
                'island' => $boracay->id,
                'category' => $catCreme->id,
                'sku' => 'CI-BRC-011',
                'name' => 'Pandan Buko Cloud',
                'slug' => 'pandan-buko-cloud',
                'short_excerpt' => 'Fragrant steeped pandan leaf infusion, coconut-oat milk, and airy pandan sweet cream foam.',
                'description' => 'Fresh knotted pandan leaves simmered slowly into chilled coconut-oat milk, finished with fluffy vanilla-pandan foam.',
                'tasting_notes' => 'Sweet garden pandan · coconut silk · floral vanilla',
                'is_perishable' => true,
                'base_price' => 195,
                'image_path' => 'assets/images/drinks/pandan-buko-cloud.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-BRC-011-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 195, 'stock_qty' => 14, 'threshold' => 5],
                ],
            ],
            [
                'island' => $boracay->id,
                'category' => $catCreme->id,
                'sku' => 'CI-BRC-012',
                'name' => 'Golden Hour Mango Fizz',
                'slug' => 'golden-hour-mango-fizz',
                'short_excerpt' => 'Sun-ripened Guimaras mango purée, passionfruit nectar, soda, and calamansi-salted rim.',
                'description' => 'Zero-caffeine tropical refresher showcasing luscious Guimaras mango layered with tart passionfruit bubbles and a salted citrus rim.',
                'tasting_notes' => 'Ripe mango · tangy passionfruit · crisp sea salt',
                'is_perishable' => true,
                'base_price' => 185,
                'image_path' => 'assets/images/drinks/golden-hour-mango-fizz.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-BRC-012-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 185, 'stock_qty' => 23, 'threshold' => 5],
                ],
            ],
            [
                'island' => $boracay->id,
                'category' => $catCreme->id,
                'sku' => 'CI-BRC-013',
                'name' => 'Barako Tres Leches Latte',
                'slug' => 'barako-tres-leches-latte',
                'short_excerpt' => 'Bold Batangas liberica-arabica espresso shaken with house vanilla three-milk blend.',
                'description' => 'Full-bodied Batangas barako blend tamed with oat milk, sweet condensed milk, and spiced vanilla bean cream.',
                'tasting_notes' => 'Jackfruit-smoke barako · sweet cream · Madagascar vanilla',
                'is_perishable' => true,
                'base_price' => 185,
                'image_path' => 'assets/images/drinks/barako-tres-leches-latte.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-BRC-013-HOT', 'label' => 'Hot 12oz', 'serve_temp' => 'hot', 'size_option' => '12oz', 'price' => 185, 'stock_qty' => 17, 'threshold' => 5],
                    ['code' => 'CI-BRC-013-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 195, 'stock_qty' => 20, 'threshold' => 5],
                ],
            ],

            // --- EL NIDO SERIES (4 DRINKS) ---
            [
                'island' => $elNido->id,
                'category' => $catBotanical->id,
                'sku' => 'CI-ELN-014',
                'name' => 'Big Lagoon Butterfly Pea Limonada',
                'slug' => 'big-lagoon-butterfly-pea-limonada',
                'short_excerpt' => 'Hand-steeped blue ternate flower infusion over Palawan wild honey and fresh lime juice.',
                'description' => 'Sapphire-blue butterfly pea blossoms steeped fresh, shifting naturally to deep violet as Palawan wild honey and crisp lime mingle over ice.',
                'tasting_notes' => 'Botanical blue ternate · wildflower honey · zesty lime',
                'is_perishable' => true,
                'base_price' => 175,
                'image_path' => 'assets/images/drinks/big-lagoon-butterfly-pea-limonada.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-ELN-014-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 175, 'stock_qty' => 24, 'threshold' => 5],
                ],
            ],
            [
                'island' => $elNido->id,
                'category' => $catBotanical->id,
                'sku' => 'CI-ELN-015',
                'name' => 'Nacpan Hibiscus Ginger Cooler',
                'slug' => 'nacpan-hibiscus-ginger-cooler',
                'short_excerpt' => 'Ruby roselle hibiscus tea, cold-pressed yellow ginger, lemongrass, and sparkling spring water.',
                'description' => 'Tart crimson roselle petals brewed with fiery yellow ginger root and fragrant tanglad lemongrass for a sparkling afternoon reset.',
                'tasting_notes' => 'Tart hibiscus · warm ginger snap · coastal lemongrass',
                'is_perishable' => true,
                'base_price' => 180,
                'image_path' => 'assets/images/drinks/nacpan-hibiscus-ginger-cooler.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-ELN-015-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 180, 'stock_qty' => 18, 'threshold' => 5],
                ],
            ],
            [
                'island' => $elNido->id,
                'category' => $catBotanical->id,
                'sku' => 'CI-ELN-016',
                'name' => 'Palawan Golden Turmeric Latte',
                'slug' => 'palawan-golden-turmeric-latte',
                'short_excerpt' => 'Organic turmeric root, Ceylon cinnamon, cracked black pepper, wild honey, and oat milk.',
                'description' => 'Soothing anti-inflammatory golden milk crafted from freshly grated turmeric, aromatic spice, and steamed barista oat milk.',
                'tasting_notes' => 'Earthy turmeric · sweet cinnamon · comforting warmth',
                'is_perishable' => true,
                'base_price' => 180,
                'image_path' => 'assets/images/drinks/palawan-golden-turmeric-latte.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-ELN-016-HOT', 'label' => 'Hot 12oz', 'serve_temp' => 'hot', 'size_option' => '12oz', 'price' => 180, 'stock_qty' => 15, 'threshold' => 5],
                    ['code' => 'CI-ELN-016-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 190, 'stock_qty' => 14, 'threshold' => 5],
                ],
            ],
            [
                'island' => $elNido->id,
                'category' => $catBotanical->id,
                'sku' => 'CI-ELN-017',
                'name' => 'Miniloc Lychee Rose White Tea',
                'slug' => 'miniloc-lychee-rose-white-tea',
                'short_excerpt' => 'Cold-brewed silver needle white tea, crushed lychee, rosewater, and hydrated chia pearls.',
                'description' => 'Delicate 12-hour cold-steeped white tea gently scented with Damascus rosewater, whole sweet lychee fruit, and textured chia.',
                'tasting_notes' => 'Juicy lychee · soft rose petal · delicate white tea',
                'is_perishable' => true,
                'base_price' => 185,
                'image_path' => 'assets/images/drinks/miniloc-lychee-rose-white-tea.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-ELN-017-ICE', 'label' => 'Iced 16oz', 'serve_temp' => 'iced', 'size_option' => '16oz', 'price' => 185, 'stock_qty' => 16, 'threshold' => 5],
                ],
            ],

            // --- ISLA MERCH CAPSULE (8 CURATED SUOT-ISLA PIECES) ---
            [
                'island' => $laUnion->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-018',
                'name' => 'La Union 5-Panel Corduroy Café Cap',
                'slug' => 'la-union-5-panel-corduroy-cafe-cap',
                'short_excerpt' => 'Unstructured coastal corduroy 5-panel cap carried from the original Suot-Isla drop.',
                'description' => 'Sun-washed wide-wale cotton corduroy cap with stitched tonal eyelet vents and brass slider buckle, built for post-surf matcha mornings.',
                'tasting_notes' => '100% cotton corduroy · adjustable brass strap',
                'is_perishable' => false,
                'base_price' => 790,
                'image_path' => 'assets/images/merch/launion-corduroy-surf-cap.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-MCH-018-OS', 'label' => 'One Size · Sand', 'serve_temp' => 'na', 'size_option' => 'One Size', 'price' => 790, 'stock_qty' => 2, 'threshold' => 5],
                ],
            ],
            [
                'island' => $laUnion->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-019',
                'name' => 'La Union Salt-Washed Oversized Tee',
                'slug' => 'la-union-salt-washed-oversized-tee',
                'short_excerpt' => 'Heavyweight 260gsm enzyme-washed cotton tee with relaxed drop shoulder.',
                'description' => 'Soft pigment-dyed coastal jersey tee designed for easy café lounging and weekend San Juan runs.',
                'tasting_notes' => '260gsm combed cotton · enzyme salt-wash',
                'is_perishable' => false,
                'base_price' => 1150,
                'image_path' => 'assets/images/merch/launion-salt-washed-oversized-tee.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-MCH-019-S', 'label' => 'Size S', 'serve_temp' => 'na', 'size_option' => 'S', 'price' => 1150, 'stock_qty' => 10, 'threshold' => 4],
                    ['code' => 'CI-MCH-019-M', 'label' => 'Size M', 'serve_temp' => 'na', 'size_option' => 'M', 'price' => 1150, 'stock_qty' => 14, 'threshold' => 4],
                    ['code' => 'CI-MCH-019-L', 'label' => 'Size L', 'serve_temp' => 'na', 'size_option' => 'L', 'price' => 1150, 'stock_qty' => 8, 'threshold' => 4],
                    ['code' => 'CI-MCH-019-XL', 'label' => 'Size XL', 'serve_temp' => 'na', 'size_option' => 'XL', 'price' => 1150, 'stock_qty' => 4, 'threshold' => 4],
                ],
            ],
            [
                'island' => $laUnion->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-020',
                'name' => 'La Union San Juan Camp-Collar Shirt',
                'slug' => 'la-union-san-juan-camp-collar-shirt',
                'short_excerpt' => 'Breezy open-collar rayon-linen resort shirt with coastal arch motif.',
                'description' => 'Signature resort button-up from Suot-Isla finished with coconut shell buttons and breathable coastal weave.',
                'tasting_notes' => 'Linen-rayon blend · coconut buttons',
                'is_perishable' => false,
                'base_price' => 1690,
                'image_path' => 'assets/images/merch/launion-sanjuan-camp-collar-shirt.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-MCH-020-M', 'label' => 'Size M', 'serve_temp' => 'na', 'size_option' => 'M', 'price' => 1690, 'stock_qty' => 9, 'threshold' => 4],
                    ['code' => 'CI-MCH-020-L', 'label' => 'Size L', 'serve_temp' => 'na', 'size_option' => 'L', 'price' => 1690, 'stock_qty' => 6, 'threshold' => 4],
                ],
            ],
            [
                'island' => $boracay->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-021',
                'name' => 'Boracay Woven Buri & Pandan Tote',
                'slug' => 'boracay-woven-buri-pandan-tote',
                'short_excerpt' => 'Handwoven Philippine buri and pandan carryall trimmed in terracotta canvas.',
                'description' => 'Artisan-plaited market tote built to carry tumblers, linen reads, and island bottles with structured woven handles.',
                'tasting_notes' => 'Natural buri & pandan · canvas strap',
                'is_perishable' => false,
                'base_price' => 1450,
                'image_path' => 'assets/images/merch/boracay-woven-buri-pandan-tote.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-MCH-021-OS', 'label' => 'One Size · Natural', 'serve_temp' => 'na', 'size_option' => 'One Size', 'price' => 1450, 'stock_qty' => 12, 'threshold' => 5],
                ],
            ],
            [
                'island' => $boracay->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-022',
                'name' => 'Boracay Golden Hour Linen Polo',
                'slug' => 'boracay-golden-hour-linen-polo',
                'short_excerpt' => 'Relaxed-tailored pure coastal linen polo in sun-warmed cream & olive.',
                'description' => 'Airy single-pocket resort polo crafted from breathable woven flax linen that softens with every island wash.',
                'tasting_notes' => '100% breathable flax linen · relaxed cut',
                'is_perishable' => false,
                'base_price' => 1790,
                'image_path' => 'assets/images/merch/boracay-golden-hour-linen-polo.jpg',
                'is_featured' => true,
                'variants' => [
                    ['code' => 'CI-MCH-022-S', 'label' => 'Size S', 'serve_temp' => 'na', 'size_option' => 'S', 'price' => 1790, 'stock_qty' => 7, 'threshold' => 4],
                    ['code' => 'CI-MCH-022-M', 'label' => 'Size M', 'serve_temp' => 'na', 'size_option' => 'M', 'price' => 1790, 'stock_qty' => 11, 'threshold' => 4],
                    ['code' => 'CI-MCH-022-L', 'label' => 'Size L', 'serve_temp' => 'na', 'size_option' => 'L', 'price' => 1790, 'stock_qty' => 5, 'threshold' => 4],
                    ['code' => 'CI-MCH-022-XL', 'label' => 'Size XL', 'serve_temp' => 'na', 'size_option' => 'XL', 'price' => 1790, 'stock_qty' => 0, 'threshold' => 4],
                ],
            ],
            [
                'island' => $elNido->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-023',
                'name' => 'El Nido Waffle-Weave Travel Towel',
                'slug' => 'el-nido-waffle-weave-travel-towel',
                'short_excerpt' => 'Quick-dry honeycomb waffle towel designed to pack light for lagoon crossings.',
                'description' => 'Sand-shedding organic cotton waffle weave that dries in half the time and doubles as a café patio wrap.',
                'tasting_notes' => 'Honeycomb waffle cotton · ultra-packable',
                'is_perishable' => false,
                'base_price' => 890,
                'image_path' => 'assets/images/merch/elnido-waffle-weave-travel-towel.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-MCH-023-OS', 'label' => 'One Size · Seafoam', 'serve_temp' => 'na', 'size_option' => 'One Size', 'price' => 890, 'stock_qty' => 19, 'threshold' => 5],
                ],
            ],
            [
                'island' => $elNido->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-024',
                'name' => 'El Nido Nacpan Round Woven Throw',
                'slug' => 'el-nido-nacpan-round-woven-throw',
                'short_excerpt' => 'Fringed circular cotton-linen picnic and beach mat in sun-bleached beige.',
                'description' => 'Generous 150cm circular throw loom-woven with tasseled trim for morning iced matcha picnics on the shoreline.',
                'tasting_notes' => '150cm diameter · cotton-linen jacquard',
                'is_perishable' => false,
                'base_price' => 1650,
                'image_path' => 'assets/images/merch/elnido-nacpan-round-beach-throw.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-MCH-024-OS', 'label' => 'One Size · Beige', 'serve_temp' => 'na', 'size_option' => 'One Size', 'price' => 1650, 'stock_qty' => 8, 'threshold' => 4],
                ],
            ],
            [
                'island' => $siargao->id,
                'category' => $catMerch->id,
                'sku' => 'CI-MCH-025',
                'name' => 'Siargao Reef-Walker Dry Bag 15L',
                'slug' => 'siargao-reef-walker-dry-bag-15l',
                'short_excerpt' => 'Waterproof roll-top 15L dry pack in lagoon teal for reef boat transfers.',
                'description' => 'Welded seam TPU tarpaulin roll-top bag sized to protect your tumbler, film camera, and dry linen shirt across island hops.',
                'tasting_notes' => '15L welded seams · detachable shoulder strap',
                'is_perishable' => false,
                'base_price' => 1190,
                'image_path' => 'assets/images/merch/siargao-reef-walker-dry-bag.jpg',
                'is_featured' => false,
                'variants' => [
                    ['code' => 'CI-MCH-025-OS', 'label' => '15L · Lagoon Teal', 'serve_temp' => 'na', 'size_option' => '15L', 'price' => 1190, 'stock_qty' => 13, 'threshold' => 5],
                ],
            ],
        ];

        $productsBySlug = [];
        $variantsByCode = [];

        foreach ($catalog as $item) {
            $product = Product::create([
                'island_series_id' => $item['island'],
                'category_id' => $item['category'],
                'sku' => $item['sku'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'short_excerpt' => $item['short_excerpt'],
                'description' => $item['description'],
                'tasting_notes' => $item['tasting_notes'],
                'is_perishable' => $item['is_perishable'],
                'base_price' => $item['base_price'],
                'image_path' => $item['image_path'],
                'is_featured' => $item['is_featured'],
                'is_archived' => false,
            ]);

            $productsBySlug[$item['slug']] = $product;

            foreach ($item['variants'] as $v) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_code' => $v['code'],
                    'label' => $v['label'],
                    'serve_temp' => $v['serve_temp'],
                    'size_option' => $v['size_option'],
                    'price' => $v['price'],
                    'stock_qty' => $v['stock_qty'],
                    'low_stock_threshold' => $v['threshold'],
                    'is_active' => true,
                ]);
                $variantsByCode[$v['code']] = $variant;
            }
        }

        // 6. Coupons
        $couponIsla10 = Coupon::create([
            'code' => 'ISLA10',
            'description' => '10% off archipelago drinks & coastal merch (min ₱300)',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_subtotal' => 300,
            'is_active' => true,
            'expires_at' => now()->addMonths(6),
        ]);

        Coupon::create([
            'code' => 'FREESIP',
            'description' => '₱65 off local rider delivery (min ₱500)',
            'discount_type' => 'fixed',
            'discount_value' => 65,
            'min_subtotal' => 500,
            'is_active' => true,
            'expires_at' => now()->addMonths(6),
        ]);

        // 7. Historical Orders, Payments, Deliveries, Return Requests & Email Logs
        $orderDefinitions = [
            [
                'order_number' => 'CI-2026-1001',
                'user' => $customer,
                'coupon' => $couponIsla10,
                'zone' => 'local_express',
                'status' => 'delivered',
                'payment_method' => 'gcash',
                'payment_status' => 'paid',
                'delivery_status' => 'delivered',
                'courier' => $courier,
                'placed_at' => now()->subDays(5),
                'items' => [
                    ['variant' => $variantsByCode['CI-LU-002-ICE'], 'qty' => 2],
                    ['variant' => $variantsByCode['CI-LU-005-ICE'], 'qty' => 1],
                ],
            ],
            [
                'order_number' => 'CI-2026-1002',
                'user' => $customer,
                'coupon' => null,
                'zone' => 'provincial_parcel',
                'status' => 'returned',
                'payment_method' => 'gcash',
                'payment_status' => 'refunded',
                'delivery_status' => 'delivered',
                'courier' => $courier,
                'placed_at' => now()->subDays(9),
                'items' => [
                    ['variant' => $variantsByCode['CI-MCH-022-M'], 'qty' => 1],
                ],
            ],
            [
                'order_number' => 'CI-2026-1003',
                'user' => $customer,
                'coupon' => null,
                'zone' => 'local_express',
                'status' => 'shipped',
                'payment_method' => 'gcash',
                'payment_status' => 'paid',
                'delivery_status' => 'in_transit',
                'courier' => $courier,
                'placed_at' => now()->subHours(2),
                'items' => [
                    ['variant' => $variantsByCode['CI-LU-001-HOT'], 'qty' => 1],
                    ['variant' => $variantsByCode['CI-MCH-018-OS'], 'qty' => 1],
                ],
            ],
            [
                'order_number' => 'CI-2026-1004',
                'user' => $customer,
                'coupon' => $couponIsla10,
                'zone' => 'local_express',
                'status' => 'pending',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'delivery_status' => 'unassigned',
                'courier' => null,
                'placed_at' => now()->subMinutes(35),
                'items' => [
                    ['variant' => $variantsByCode['CI-LU-003-ICE'], 'qty' => 1],
                    ['variant' => $variantsByCode['CI-LU-004-ICE'], 'qty' => 1],
                ],
            ],
            [
                'order_number' => 'CI-2026-1005',
                'user' => $customerTwo,
                'coupon' => null,
                'zone' => 'local_express',
                'status' => 'confirmed',
                'payment_method' => 'gcash',
                'payment_status' => 'paid',
                'delivery_status' => 'assigned',
                'courier' => $courier,
                'placed_at' => now()->subHours(5),
                'items' => [
                    ['variant' => $variantsByCode['CI-SRG-006-ICE'], 'qty' => 2],
                    ['variant' => $variantsByCode['CI-BRC-010-DIRTY'], 'qty' => 1],
                ],
            ],
            [
                'order_number' => 'CI-2026-1006',
                'user' => $customerThree,
                'coupon' => null,
                'zone' => 'local_express',
                'status' => 'cancelled',
                'payment_method' => 'gcash',
                'payment_status' => 'refunded',
                'delivery_status' => 'unassigned',
                'courier' => null,
                'placed_at' => now()->subDays(2),
                'items' => [
                    ['variant' => $variantsByCode['CI-ELN-014-ICE'], 'qty' => 2],
                ],
            ],
        ];

        $seededOrders = [];
        foreach ($orderDefinitions as $def) {
            $subtotal = 0;
            foreach ($def['items'] as $entry) {
                $subtotal += (float) $entry['variant']->price * $entry['qty'];
            }
            $discount = $def['coupon'] ? $def['coupon']->computeDiscount($subtotal) : 0;
            $afterDiscount = max(0, $subtotal - $discount);
            $deliveryFee = $afterDiscount >= 1200 ? 0 : ($def['zone'] === 'local_express' ? 65 : 120);
            $grandTotal = round($afterDiscount + $deliveryFee, 2);

            $order = Order::create([
                'order_number' => $def['order_number'],
                'user_id' => $def['user']->id,
                'coupon_id' => $def['coupon']?->id,
                'billing_name' => $def['user']->name,
                'billing_email' => $def['user']->email,
                'billing_phone' => $def['user']->phone ?? '0917-555-0100',
                'shipping_recipient' => $def['user']->name,
                'shipping_phone' => $def['user']->phone ?? '0917-555-0100',
                'shipping_address_line' => $def['zone'] === 'provincial_parcel'
                    ? '42 Acacia Terraces, Lahug'
                    : '18 Urbiztondo Coastal Lane',
                'shipping_city' => $def['zone'] === 'provincial_parcel' ? 'Cebu City' : 'San Juan',
                'shipping_province' => $def['zone'] === 'provincial_parcel' ? 'Cebu' : 'La Union',
                'shipping_postal_code' => $def['zone'] === 'provincial_parcel' ? '6000' : '2514',
                'delivery_zone' => $def['zone'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'delivery_fee' => $deliveryFee,
                'grand_total' => $grandTotal,
                'status' => $def['status'],
                'order_notes' => 'Less ice on chilled matcha jars; leave at gate veranda.',
                'placed_at' => $def['placed_at'],
            ]);

            $createdItems = [];
            foreach ($def['items'] as $entry) {
                $v = $entry['variant'];
                $p = $v->product;
                $createdItems[] = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $v->id,
                    'product_name_snapshot' => $p->name,
                    'variant_label_snapshot' => $v->label,
                    'is_perishable' => $p->is_perishable,
                    'quantity' => $entry['qty'],
                    'unit_price' => $v->price,
                    'line_total' => round((float) $v->price * $entry['qty'], 2),
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $def['payment_method'],
                'reference_number' => 'PAY-'.strtoupper(substr(md5($def['order_number']), 0, 8)),
                'amount' => $grandTotal,
                'refunded_amount' => $def['payment_status'] === 'refunded' ? $grandTotal : 0,
                'status' => $def['payment_status'],
                'paid_at' => in_array($def['payment_status'], ['paid', 'refunded']) ? $def['placed_at'] : null,
                'refunded_at' => $def['payment_status'] === 'refunded' ? $def['placed_at']->copy()->addHours(4) : null,
            ]);

            Delivery::create([
                'order_id' => $order->id,
                'courier_user_id' => $def['courier']?->id,
                'tracking_number' => 'TRK-'.substr($def['order_number'], -4).'-ISLA',
                'carrier_name' => $def['zone'] === 'local_express' ? 'Isla Express Rider' : 'Archipelago Courier Parcel',
                'status' => $def['delivery_status'],
                'shipped_at' => in_array($def['delivery_status'], ['in_transit', 'delivered']) ? $def['placed_at']->copy()->addMinutes(40) : null,
                'delivered_at' => $def['delivery_status'] === 'delivered' ? $def['placed_at']->copy()->addMinutes(75) : null,
                'proof_notes' => $def['delivery_status'] === 'delivered' ? 'Signed at front porch by recipient.' : null,
            ]);

            $seededOrders[$def['order_number']] = ['order' => $order, 'items' => $createdItems];
        }

        // 8. Return Requests (1 Pending Spill Refund + 1 Approved Merch Return with Restock Applied)
        ReturnRequest::create([
            'order_id' => $seededOrders['CI-2026-1001']['order']->id,
            'order_item_id' => $seededOrders['CI-2026-1001']['items'][0]->id,
            'user_id' => $customer->id,
            'request_type' => 'spill_refund',
            'reason' => 'Lid seal loosened on one iced cup during coastal transit.',
            'details' => 'One Monaliza Caramel Matcha lost sea-salt foam cap in carrier bag; requesting partial beverage refund.',
            'status' => 'pending',
            'restock_applied' => false,
            'refund_amount' => 205.00,
        ]);

        ReturnRequest::create([
            'order_id' => $seededOrders['CI-2026-1002']['order']->id,
            'order_item_id' => $seededOrders['CI-2026-1002']['items'][0]->id,
            'user_id' => $customer->id,
            'request_type' => 'return',
            'reason' => 'Exchanging Size M linen polo for relaxed fit.',
            'details' => 'Unworn with original swing tag intact.',
            'status' => 'approved',
            'restock_applied' => true,
            'refund_amount' => $seededOrders['CI-2026-1002']['order']->grand_total,
            'processed_by' => $admin->id,
            'admin_remarks' => 'Unworn Isla Merch inspected and restocked into Boracay Linen Polo Size M inventory.',
        ]);

        // 9. Sample Email Logs
        $order1 = $seededOrders['CI-2026-1001']['order'];
        $order3 = $seededOrders['CI-2026-1003']['order'];
        $order2 = $seededOrders['CI-2026-1002']['order'];
        $order6 = $seededOrders['CI-2026-1006']['order'];

        EmailLog::create([
            'user_id' => $customer->id,
            'order_id' => $order1->id,
            'recipient_email' => $customer->email,
            'event_type' => 'order_confirmation',
            'subject' => 'Café Isla · Order Confirmation #CI-2026-1001',
            'body_html' => '<div style="font-family:Georgia,serif;background:#F6F1E6;color:#261D18;padding:24px;border:1px solid #261D18;"><h2>Salamat, Aria — Your Archipelago Pour is Confirmed</h2><p>Order <strong>#CI-2026-1001</strong> (2x Monaliza Caramel Matcha, 1x San Juan Spanish Hojicha) has been received and queued for ceremonial whisking at our San Juan bar.</p><p><strong>Total Paid (GCash): ₱'.number_format((float) $order1->grand_total, 2).'</strong></p></div>',
            'smtp_status' => 'delivered (dual-log)',
            'sent_at' => $order1->placed_at,
        ]);

        EmailLog::create([
            'user_id' => $customer->id,
            'order_id' => $order3->id,
            'recipient_email' => $customer->email,
            'event_type' => 'shipping_update',
            'subject' => 'Café Isla · Rider En Route for Order #CI-2026-1003',
            'body_html' => '<div style="font-family:Georgia,serif;background:#F6F1E6;color:#261D18;padding:24px;border:1px solid #261D18;"><h2>Your Isla Express Rider Is On The Way</h2><p>Rider <strong>Jomar Dela Cruz</strong> picked up Order <strong>#CI-2026-1003</strong> (Urbiztondo Morning Matcha Hot 12oz + La Union 5-Panel Corduroy Café Cap). Tracking: <code>TRK-1003-ISLA</code>.</p></div>',
            'smtp_status' => 'delivered (dual-log)',
            'sent_at' => now()->subMinutes(50),
        ]);

        EmailLog::create([
            'user_id' => $customer->id,
            'order_id' => $order2->id,
            'recipient_email' => $customer->email,
            'event_type' => 'return_update',
            'subject' => 'Café Isla · Return Approved & Merch Restocked (#CI-2026-1002)',
            'body_html' => '<div style="font-family:Georgia,serif;background:#F6F1E6;color:#261D18;padding:24px;border:1px solid #261D18;"><h2>Return Approved & Refund Issued</h2><p>Your return for <strong>Boracay Golden Hour Linen Polo (Size M)</strong> has been verified. Inventory restocked (+1) and ₱'.number_format((float) $order2->grand_total, 2).' refunded to GCash.</p></div>',
            'smtp_status' => 'delivered (dual-log)',
            'sent_at' => $order2->placed_at->copy()->addHours(4),
        ]);

        EmailLog::create([
            'user_id' => $customerThree->id,
            'order_id' => $order6->id,
            'recipient_email' => $customerThree->email,
            'event_type' => 'order_cancellation',
            'subject' => 'Café Isla · Order #CI-2026-1006 Cancelled & Refunded',
            'body_html' => '<div style="font-family:Georgia,serif;background:#F6F1E6;color:#261D18;padding:24px;border:1px solid #261D18;"><h2>Order Cancelled Before Prep</h2><p>Order <strong>#CI-2026-1006</strong> was cancelled cleanly prior to drink preparation and refunded in full.</p></div>',
            'smtp_status' => 'delivered (dual-log)',
            'sent_at' => $order6->placed_at->copy()->addMinutes(20),
        ]);

        // 10. Shop Visits (Analytics: Conversion & Cart Abandonment)
        foreach ($seededOrders as $entry) {
            ShopVisit::create([
                'session_token' => 'seed-conv-'.$entry['order']->order_number,
                'user_id' => $entry['order']->user_id,
                'route_name' => 'checkout.store',
                'had_cart' => true,
                'converted_order_id' => $entry['order']->id,
                'visited_at' => $entry['order']->placed_at,
            ]);
        }

        // 9 abandoned carts
        for ($i = 1; $i <= 9; $i++) {
            ShopVisit::create([
                'session_token' => 'seed-aband-'.$i,
                'user_id' => $i <= 3 ? $customerTwo->id : null,
                'route_name' => 'cart.index',
                'had_cart' => true,
                'converted_order_id' => null,
                'visited_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        // 25 browse visits
        for ($i = 1; $i <= 25; $i++) {
            ShopVisit::create([
                'session_token' => 'seed-browse-'.$i,
                'user_id' => null,
                'route_name' => $i % 2 === 0 ? 'menu.index' : 'home',
                'had_cart' => false,
                'converted_order_id' => null,
                'visited_at' => now()->subDays(rand(0, 7)),
            ]);
        }
    }
}
