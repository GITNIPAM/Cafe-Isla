<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('recipient_name');
            $table->string('phone', 32);
            $table->string('street_address');
            $table->string('barangay', 120)->nullable();
            $table->string('city', 120);
            $table->string('province', 120);
            $table->string('postal_code', 20);
            $table->enum('delivery_zone', ['local_express', 'provincial_parcel'])->default('local_express');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('island_series', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('number_label', 10);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle');
            $table->string('coordinates', 80);
            $table->text('terroir_story');
            $table->string('accent_hex', 16);
            $table->string('hero_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('product_type', ['drink', 'merch'])->default('drink');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('island_series_id')->constrained('island_series')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('sku', 40)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_excerpt');
            $table->text('description');
            $table->string('tasting_notes')->nullable();
            $table->boolean('is_perishable')->default(true);
            $table->decimal('base_price', 10, 2);
            $table->string('image_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('variant_code', 50)->unique();
            $table->string('label', 80);
            $table->enum('serve_temp', ['hot', 'iced', 'na'])->default('na');
            $table->string('size_option', 40)->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description');
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_subtotal', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token', 100)->unique();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->enum('status', ['active', 'converted', 'abandoned'])->default('active');
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('custom_notes')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('billing_name');
            $table->string('billing_email');
            $table->string('billing_phone', 32);
            $table->string('shipping_recipient');
            $table->string('shipping_phone', 32);
            $table->string('shipping_address_line');
            $table->string('shipping_city', 120);
            $table->string('shipping_province', 120);
            $table->string('shipping_postal_code', 20);
            $table->enum('delivery_zone', ['local_express', 'provincial_parcel'])->default('local_express');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled', 'returned'])->default('pending');
            $table->text('order_notes')->nullable();
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_label_snapshot', 100);
            $table->boolean('is_perishable')->default(true);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->enum('method', ['gcash', 'cod'])->default('gcash');
            $table->string('reference_number', 80);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('courier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tracking_number', 60)->unique();
            $table->string('carrier_name', 100)->default('Isla Express Rider');
            $table->enum('status', ['unassigned', 'assigned', 'in_transit', 'delivered', 'failed'])->default('unassigned');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('proof_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('request_type', ['cancellation', 'return', 'spill_refund'])->default('return');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->boolean('restock_applied')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('recipient_email');
            $table->enum('event_type', ['order_confirmation', 'shipping_update', 'order_cancellation', 'return_update']);
            $table->string('subject');
            $table->longText('body_html');
            $table->string('smtp_status', 60)->default('logged');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('shop_visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_name', 100);
            $table->boolean('had_cart')->default(false);
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_visits');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('island_series');
        Schema::dropIfExists('user_addresses');
    }
};
