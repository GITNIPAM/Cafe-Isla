<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
        $this->get('/track?order=CI-2026-1003')->assertStatus(200)->assertSee('TRK-1003-ISLA');
        $this->get('/track?order=TRK-1003-ISLA')->assertStatus(200)->assertSee('CI-2026-1003');
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
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
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
        $this->assertDatabaseHas('email_logs', [
            'order_id' => $order->id,
            'event_type' => 'order_confirmation',
        ]);

        // Admin ships order -> logs shipping_update email
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $courier = User::where('email', 'rider@cafeisla.ph')->firstOrFail();
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

    public function test_admin_can_upload_product_image_and_create_item(): void
    {
        $admin = User::where('email', 'admin@cafeisla.ph')->firstOrFail();
        $tmpCopy = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cafe_isla_upload_test.jpg';
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
        $this->assertFileExists(public_path($created->image_path));

        @unlink(public_path($created->image_path));
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
