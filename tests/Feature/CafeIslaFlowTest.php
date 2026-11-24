<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CafeIslaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_storefront_and_la_union_pinned_drinks_render_and_filter(): void
    {
        $home = $this->get('/');
        $home->assertStatus(200)
            ->assertSee('Café Isla')
            ->assertSee('Urbiztondo Morning Matcha')
            ->assertSee('Monaliza Caramel Matcha')
            ->assertSee('Carille Coco Matcha')
            ->assertSee('Tangadan Berry Matcha')
            ->assertSee('San Juan Spanish Hojicha');

        $filtered = $this->get('/menu?island=la-union&serve_temp=iced');
        $filtered->assertStatus(200)
            ->assertSee('Monaliza Caramel Matcha')
            ->assertDontSee('Cloud 9 Sea Salt Latte');

        $search = $this->get('/menu?q=Hojicha');
        $search->assertStatus(200)
            ->assertSee('San Juan Spanish Hojicha')
            ->assertDontSee('Urbiztondo Morning Matcha');

        $this->get('/diagrams')->assertStatus(200)->assertSee('Normalized Relational Schema');
<<<<<<< HEAD
        $customer = User::where('email', 'customer@cafeisla.ph')->firstOrFail();
        $this->actingAs($customer)->get('/track?order=CI-2026-1003')->assertStatus(200)->assertSee('TRK-1003-ISLA');
        $this->actingAs($customer)->get('/track?order=TRK-1003-ISLA')->assertStatus(200)->assertSee('CI-2026-1003');
=======

        $this->get('/product/muni-matcha')->assertStatus(301)
            ->assertRedirect('/product/urbiztondo-morning-matcha');
        foreach (Product::all() as $product) {
            $this->assertFileExists(public_path(parse_url($product->imageUrl(), PHP_URL_PATH)));
        }
        $legacy = new Product(['image_path' => 'assets/images/drinks/muni-matcha.jpg']);
        $this->assertStringEndsWith('/urbiztondo-morning-matcha.jpg', $legacy->imageUrl());
>>>>>>> 3fd5ec3e2ff9dfbca64583b442277f949a382b08
    }

    public function test_bcrypt_registration_and_rbac_portal_permissions(): void
    {
        $res = $this->post('/auth/register', [
            'name' => 'Kiana Reyes',
            'email' => 'kiana@example.com',
            'phone' => '0917-123-4567',
            'password' => 'SecretIsla123!',
            'password_confirmation' => 'SecretIsla123!',
        ]);
        $res->assertRedirect('/account');

        $newUser = User::where('email', 'kiana@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('SecretIsla123!', $newUser->password));
        $this->assertNotEquals('SecretIsla123!', $newUser->password);

        // Customer cannot enter admin portal
        $this->actingAs($newUser)->get('/admin')->assertStatus(403);

        // Courier can access /admin orders but cannot create products or team members
        $courier = User::where('email', 'rider@cafeisla.ph')->firstOrFail();
        $this->actingAs($courier)->get('/admin')->assertStatus(200)->assertSee('Courier Dispatch');
        $this->actingAs($courier)->post('/admin/categories', ['name' => 'Blocked', 'product_type' => 'drink'])->assertStatus(403);
        $this->actingAs($courier)->post('/admin/team', [
            'name' => 'Blocked',
            'email' => 'b@cafeisla.ph',
            'role' => 'admin',
            'password' => 'Password123!',
        ])->assertStatus(403);

        // Removed Staff Barista portal: staff account cannot access /admin
        $staff = User::where('email', 'barista@cafeisla.ph')->firstOrFail();
        $this->actingAs($staff)->get('/admin')->assertStatus(403);
    }

    public function test_persistent_bag_coupon_checkout_shipping_and_cancellation(): void
    {
        $customer = User::where('email', 'customer@cafeisla.ph')->firstOrFail();
        $variant = ProductVariant::where('variant_code', 'CI-LU-001-ICE')->firstOrFail();
        $initialStock = $variant->stock_qty;

        // Add as guest first
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2, 'custom_notes' => 'Half sweet, light ice'])
            ->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('summary.count', 2);

        // Apply ISLA10 coupon
        $this->postJson('/cart/coupon', ['code' => 'ISLA10'])
            ->assertStatus(200)
            ->assertJsonPath('summary.coupon_code', 'ISLA10');

        // Login merges guest cart into user session
        $this->post('/auth/login', [
            'email' => 'customer@cafeisla.ph',
            'password' => 'Isla2026!',
        ])->assertRedirect('/account');

        // Reject unsupported payment method (only gcash & cod allowed)
        $this->post('/checkout', [
            'billing_name' => 'Aria Mendoza',
            'billing_email' => 'customer@cafeisla.ph',
            'billing_phone' => '0917-555-0188',
            'shipping_recipient' => 'Aria Mendoza',
            'shipping_phone' => '0917-555-0188',
            'shipping_address_line' => '18 Urbiztondo Coastal Lane',
            'shipping_city' => 'San Juan',
            'shipping_province' => 'La Union',
            'shipping_postal_code' => '2514',
            'delivery_zone' => 'local_express',
            'payment_method' => 'maya_card',
        ])->assertSessionHasErrors('payment_method');

        // Checkout with local express delivery
        $checkoutRes = $this->post('/checkout', [
            'billing_name' => 'Aria Mendoza',
            'billing_email' => 'customer@cafeisla.ph',
            'billing_phone' => '0917-555-0188',
            'shipping_recipient' => 'Aria Mendoza',
            'shipping_phone' => '0917-555-0188',
            'shipping_address_line' => '18 Urbiztondo Coastal Lane',
            'shipping_city' => 'San Juan',
            'shipping_province' => 'La Union',
            'shipping_postal_code' => '2514',
            'delivery_zone' => 'local_express',
            'payment_method' => 'gcash',
            'payment_account_ref' => 'GCASH-9901',
        ]);
        $checkoutRes->assertRedirect('/account');

        $variant->refresh();
        $this->assertEquals($initialStock - 2, $variant->stock_qty);

        $order = Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame('Half sweet, light ice', $order->items()->firstOrFail()->custom_notes);
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertOk()->assertJsonPath('summary.count', 1);
        $this->assertDatabaseHas('email_logs', [
            'order_id' => $order->id,
            'event_type' => 'order_confirmation',
        ]);

        // Admin ships order -> logs shipping_update email
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $courier = User::where('email', 'rider@cafeisla.ph')->firstOrFail();
        $this->actingAs($admin)->get('/admin?tab=orders')->assertSee('Half sweet, light ice');
        $this->actingAs($admin)->patch('/admin/orders/'.$order->id, [
            'status' => 'shipped',
            'courier_user_id' => $courier->id,
            'proof_notes' => 'Dispatched chilled',
        ])->assertRedirect('/admin?tab=orders');

        $this->assertDatabaseHas('email_logs', [
            'order_id' => $order->id,
            'event_type' => 'shipping_update',
        ]);

        // Courier marks delivered
        $this->actingAs($courier)->patch('/admin/orders/'.$order->id, [
            'status' => 'delivered',
            'proof_notes' => 'Handed to Aria',
        ])->assertRedirect('/admin?tab=orders');

        $this->assertEquals('delivered', $order->fresh()->status);

        // Customer cancels seeded pending order CI-2026-1004 -> restores stock & logs cancellation email
        $pendingOrder = Order::where('order_number', 'CI-2026-1004')->firstOrFail();
        $this->actingAs($customer)->post('/account/orders/'.$pendingOrder->id.'/cancel', [
            'reason' => 'Rescheduling trip',
        ])->assertRedirect();

        $this->assertEquals('cancelled', $pendingOrder->fresh()->status);
        $this->assertDatabaseHas('email_logs', [
            'order_id' => $pendingOrder->id,
            'event_type' => 'order_cancellation',
        ]);
    }

    public function test_smart_return_restocks_merch_but_never_restocks_perishable_drinks(): void
    {
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $customer = User::where('email', 'customer@cafeisla.ph')->firstOrFail();

        // 1. Seeded pending spill refund on Monaliza Caramel Matcha (perishable) -> should NOT increment stock
        $drinkReturn = ReturnRequest::where('request_type', 'spill_refund')->where('status', 'pending')->firstOrFail();
        $drinkVariant = $drinkReturn->orderItem->variant;
        $drinkStockBefore = $drinkVariant->stock_qty;

        $this->actingAs($admin)->post('/admin/returns/'.$drinkReturn->id, [
            'decision' => 'approved',
            'admin_remarks' => 'Verified transit spill; refunded without restock.',
        ])->assertRedirect('/admin?tab=returns');

        $this->assertEquals('approved', $drinkReturn->fresh()->status);
        $this->assertFalse($drinkReturn->fresh()->restock_applied);
        $this->assertEquals($drinkStockBefore, $drinkVariant->fresh()->stock_qty);

        // 2. New merch return on delivered Cap (non-perishable) -> MUST increment stock
        $order = Order::where('order_number', 'CI-2026-1003')->firstOrFail();
        $this->actingAs($admin)->patch('/admin/orders/'.$order->id, ['status' => 'delivered']);
        $merchItem = $order->items()->where('is_perishable', false)->firstOrFail();
        $merchStockBefore = $merchItem->variant->stock_qty;

        $this->actingAs($customer)->post('/account/orders/'.$order->id.'/return', [
            'order_item_id' => $merchItem->id,
            'request_type' => 'return',
            'reason' => 'Unworn cap return',
        ])->assertRedirect();

        $merchReturn = ReturnRequest::where('order_item_id', $merchItem->id)->latest('id')->firstOrFail();
        $this->actingAs($admin)->post('/admin/returns/'.$merchReturn->id, [
            'decision' => 'approved',
            'admin_remarks' => 'Tag intact, restocked.',
        ])->assertRedirect('/admin?tab=returns');

        $this->assertTrue($merchReturn->fresh()->restock_applied);
        $this->assertEquals($merchStockBefore + $merchItem->quantity, $merchItem->variant->fresh()->stock_qty);
    }

    public function test_receipts_and_tracking_are_private_and_couriers_only_see_assigned_orders(): void
    {
        $customer = User::where('email', 'customer@cafeisla.ph')->firstOrFail();
        $ownReceipt = EmailLog::where('user_id', $customer->id)->firstOrFail();
        $otherReceipt = EmailLog::where('user_id', '!=', $customer->id)->firstOrFail();
        $this->getJson('/inbox')->assertUnauthorized();
        $this->get('/track?order='.$ownReceipt->order->order_number)->assertRedirect('/auth');
        $this->actingAs($customer)->getJson('/inbox')->assertOk()
            ->assertJsonFragment(['id' => $ownReceipt->id])
            ->assertJsonMissing(['id' => $otherReceipt->id]);
        $this->get('/track?order='.$ownReceipt->order->order_number)->assertOk()->assertSee('Items Ordered');
        $this->get('/track?order='.$otherReceipt->order->order_number)->assertOk()->assertDontSee('Items Ordered');
        $this->get('/track?order='.urlencode('#'.$ownReceipt->order->delivery->tracking_number))->assertOk()->assertSee('Items Ordered');
        $this->get('/track?order='.$otherReceipt->order->delivery->tracking_number)->assertOk()->assertDontSee('Items Ordered');

        $courier = User::where('email', 'rider@cafeisla.ph')->firstOrFail();
        $otherCourier = User::factory()->create(['role' => 'courier', 'is_active' => true]);
        $order = Order::whereHas('delivery', fn ($query) => $query->where('courier_user_id', $courier->id))->firstOrFail();
        $this->actingAs($courier)->get('/admin')->assertSee($order->order_number);
        $this->actingAs($otherCourier)->get('/admin')->assertDontSee($order->order_number);
        $this->patch('/admin/orders/'.$order->id, ['status' => 'delivered'])->assertForbidden();
        $this->getJson('/inbox')->assertOk()->assertJsonCount(0, 'emails');
    }

    public function test_checkout_quotes_follow_the_address_and_current_prices(): void
    {
        $customer = User::where('email', 'customer@cafeisla.ph')->firstOrFail();
        $merch = ProductVariant::whereHas('product', fn ($query) => $query->where('is_perishable', false))->firstOrFail();
        $local = $customer->addresses()->where('is_default', true)->firstOrFail();
        $parcel = $customer->addresses()->where('delivery_zone', 'provincial_parcel')->firstOrFail();

        $merch->update(['price' => 600]);
        $this->actingAs($customer)->postJson('/cart/items', ['variant_id' => $merch->id, 'quantity' => 1])->assertOk();
        $this->get('/checkout')->assertOk()->assertSee('₱665.00');
        $this->postJson('/checkout/quote', ['saved_address_id' => $parcel->id, 'delivery_zone' => 'provincial_parcel'])
            ->assertOk()->assertJsonPath('delivery_error', null)->assertJsonPath('grand_total', 720);
        $this->postJson('/checkout/quote', ['saved_address_id' => $parcel->id, 'delivery_zone' => 'local_express'])
            ->assertOk()->assertJsonPath('delivery_error', fn ($value) => str_contains($value, 'unavailable'));
        $this->postJson('/cart/coupon', ['code' => 'FREESIP'])->assertOk();
        $this->postJson('/checkout/quote', ['saved_address_id' => $local->id, 'delivery_zone' => 'local_express'])
            ->assertJsonPath('grand_total', 600)->assertJsonPath('delivery_fee', 0);
        $this->postJson('/checkout/quote', ['saved_address_id' => $parcel->id, 'delivery_zone' => 'provincial_parcel'])
            ->assertJsonPath('grand_total', 720)->assertJsonPath('discount_amount', 0);

        $foreignAddress = UserAddress::where('user_id', '!=', $customer->id)->firstOrFail();
        $this->postJson('/checkout/quote', ['saved_address_id' => $foreignAddress->id, 'delivery_zone' => 'local_express'])
            ->assertNotFound();

        $fields = [
            'billing_name' => $customer->name,
            'billing_email' => $customer->email,
            'billing_phone' => $local->phone,
            'payment_method' => 'cod',
            'saved_address_id' => $local->id,
            'delivery_zone' => 'provincial_parcel',
            'quoted_total' => 720,
        ];
        $merch->update(['price' => 490]);
        $this->postJson('/checkout/quote', ['shipping_city' => 'Dasmarinas', 'shipping_province' => 'Cavite', 'delivery_zone' => 'local_express'])
            ->assertJsonPath('delivery_error', null)->assertJsonPath('grand_total', 555);
        $this->from('/checkout')->post('/checkout', $fields)->assertRedirect('/checkout')->assertSessionHas('error');
        $this->post('/checkout', array_replace($fields, ['quoted_total' => 610]))->assertRedirect('/account');
        $order = $customer->orders()->latest('id')->firstOrFail();
        $this->assertSame('610.00', $order->grand_total);
        $this->assertSame('provincial_parcel', $order->delivery_zone);
    }

    public function test_drink_order_cannot_skip_delivery_eligibility_or_save_an_invalid_address(): void
    {
        $customer = User::where('email', 'customer@cafeisla.ph')->firstOrFail();
        $variant = ProductVariant::where('variant_code', 'CI-LU-001-ICE')->firstOrFail();
        $this->actingAs($customer)->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])->assertOk();
        $address = ['shipping_city' => 'Cebu City', 'shipping_province' => 'Cebu', 'delivery_zone' => 'provincial_parcel'];
        $this->postJson('/checkout/quote', $address)->assertJsonPath('delivery_error', fn ($value) => str_contains($value, 'Fresh drinks'));
        $this->from('/checkout')->post('/checkout', array_replace($address, [
            'billing_name' => $customer->name, 'billing_email' => $customer->email, 'billing_phone' => '09175550188',
            'shipping_recipient' => 'Sample Recipient', 'shipping_phone' => '09175550188',
            'shipping_address_line' => 'Sample lane', 'shipping_postal_code' => '6000',
            'delivery_zone' => 'local_express', 'payment_method' => 'cod', 'save_address' => '1',
        ]))->assertRedirect('/checkout')->assertSessionHas('error');
        $this->assertDatabaseMissing('user_addresses', ['street_address' => 'Sample lane']);
        $this->assertDatabaseMissing('orders', ['shipping_address_line' => 'Sample lane']);
        $this->assertSame($variant->stock_qty, $variant->fresh()->stock_qty);
    }

    public function test_support_pages_and_newsletter_require_consent(): void
    {
        $this->get('/contact')->assertOk()->assertSee('Contact Café Isla');
        $this->get('/delivery-and-returns')->assertOk()->assertSee('Dasmariñas, Cavite');
        $this->get('/privacy')->assertOk()->assertSee('Seasonal email list');
        $this->from('/')->post('/newsletter', ['newsletter_email' => 'News@example.com'])
            ->assertRedirect('/')->assertSessionHasErrors('newsletter_consent');
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        foreach (['News@example.com', 'news@example.com'] as $email) {
            $this->from('/')->post('/newsletter', ['newsletter_email' => $email, 'newsletter_consent' => '1'])
                ->assertRedirect('/')->assertSessionHas('success');
        }
        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        $this->assertDatabaseHas('newsletter_subscriptions', ['email' => 'news@example.com']);
    }

    public function test_demo_sign_in_and_diagrams_are_hidden_when_demo_mode_is_off(): void
    {
        config(['cafeisla.demo_mode' => false]);
        $this->get('/')->assertOk()->assertDontSee(route('diagrams.index'));
        $this->get('/auth')->assertOk()->assertDontSee('data-demo-email', false);
        $this->get('/diagrams')->assertNotFound();
    }

    public function test_password_reset_uses_a_one_time_email_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => true]);
        $this->get('/forgot-password')->assertOk();
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])->assertSessionHas('info');
        $token = Notification::sent($user, ResetPassword::class)->sole()->token;
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $fields = ['email' => $user->email, 'token' => $token, 'password' => 'ChangedPassword28!', 'password_confirmation' => 'ChangedPassword28!'];
        $this->post('/reset-password', $fields)->assertRedirect('/auth');
        $this->assertTrue(Hash::check('ChangedPassword28!', $user->fresh()->password));
        $this->post('/reset-password', $fields)->assertSessionHasErrors('email');
    }

    public function test_admin_can_upload_product_image_and_create_item(): void
    {
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $imageRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cafe-isla-'.Str::uuid();
        config(['filesystems.disks.catalog_uploads.root' => $imageRoot]);
        $tmpCopy = tempnam(sys_get_temp_dir(), 'cafe-isla-upload-');
        copy(public_path('assets/images/drinks/urbiztondo-morning-matcha.jpg'), $tmpCopy);
        $file = new UploadedFile($tmpCopy, 'island-test-drink.jpg', 'image/jpeg', null, true);

        $res = $this->actingAs($admin)->post('/admin/products', [
            'island_series_id' => 1,
            'category_id' => 1,
            'sku' => 'CI-TEST-900',
            'name' => 'Urbiztondo Sesame Matcha',
            'short_excerpt' => 'Toasted black sesame cream over ceremonial matcha.',
            'description' => 'Limited test pour.',
            'tasting_notes' => 'Nutty black sesame · umami matcha',
            'is_perishable' => 1,
            'base_price' => 210,
            'variant_label' => 'Iced 16oz',
            'serve_temp' => 'iced',
            'stock_qty' => 12,
            'image' => $file,
        ]);
        $res->assertRedirect('/admin?tab=inventory');

        $created = Product::where('sku', 'CI-TEST-900')->firstOrFail();
        $this->assertStringStartsWith('uploads/products/', (string) $created->image_path);
        $disk = Storage::disk('catalog_uploads');
        $filename = basename($created->image_path);
        try {
            $this->assertFileExists($disk->path($filename));
            $this->get('/'.$created->image_path)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        } finally {
            $disk->delete($filename);
            rmdir($imageRoot);
            unlink($tmpCopy);
        }
    }

    public function test_admin_can_manage_categories_variants_and_inspect_customer_history(): void
    {
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $category = Category::firstOrFail();

        $this->actingAs($admin)->patch('/admin/categories/'.$category->id, [
            'name' => 'Ceremonial Matcha Archive',
            'product_type' => 'drink',
            'is_archived' => '1',
        ])->assertRedirect('/admin?tab=inventory');

        $this->assertTrue((bool) $category->fresh()->is_archived);
        $this->assertEquals('Ceremonial Matcha Archive', $category->fresh()->name);

        $product = Product::firstOrFail();
        $this->actingAs($admin)->post('/admin/products/'.$product->id.'/variants', [
            'label' => 'Iced 20oz Pitcher',
            'serve_temp' => 'iced',
            'price' => 260,
            'stock_qty' => 8,
        ])->assertRedirect('/admin?tab=inventory');

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'label' => 'Iced 20oz Pitcher',
            'stock_qty' => 8,
        ]);

        $teamView = $this->actingAs($admin)->get('/admin?tab=team');
        $teamView->assertStatus(200);
        $teamView->assertSee('CI-2026-1001');
        $teamView->assertSee('Urbiztondo Coastal Lane');
        $teamView->assertSee('spill_refund');
    }

    public function test_order_fulfillment_pipeline_steps_progress_linearly(): void
    {
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $courier = User::where('email', 'rider@cafeisla.ph')->firstOrFail();
        $order = Order::where('order_number', 'CI-2026-1003')->firstOrFail();

        $this->actingAs($admin)->patch('/admin/orders/'.$order->id, ['status' => 'confirmed'])->assertRedirect('/admin?tab=orders');
        $this->assertEquals('confirmed', $order->fresh()->status);

        $this->actingAs($admin)->patch('/admin/orders/'.$order->id, ['status' => 'preparing'])->assertRedirect('/admin?tab=orders');
        $this->assertEquals('preparing', $order->fresh()->status);

        $this->actingAs($admin)->patch('/admin/orders/'.$order->id, [
            'status' => 'shipped',
            'delivery_status' => 'assigned',
            'courier_user_id' => $courier->id,
        ])->assertRedirect('/admin?tab=orders');
        $this->assertEquals('shipped', $order->fresh()->status);
        $this->assertEquals('assigned', $order->fresh()->delivery->status);

        $this->actingAs($courier)->patch('/admin/orders/'.$order->id, [
            'status' => 'shipped',
            'delivery_status' => 'in_transit',
            'proof_notes' => 'Rider picked up at San Juan bar',
        ])->assertRedirect('/admin?tab=orders');
        $this->assertEquals('in_transit', $order->fresh()->delivery->status);

        $this->actingAs($courier)->patch('/admin/orders/'.$order->id, [
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'proof_notes' => 'Handed to recipient',
        ])->assertRedirect('/admin?tab=orders');
        $this->assertEquals('delivered', $order->fresh()->status);
        $this->assertEquals('delivered', $order->fresh()->delivery->status);
    }
}
