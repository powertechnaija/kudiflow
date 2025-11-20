<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $store;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::factory()->create();
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
        $this->user = User::factory()->create(['store_id' => $this->store->id]);
    }

    public function test_cannot_sell_more_stock_than_available()
    {
        $product = Product::create(['store_id' => $this->store->id, 'name' => 'Item']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'store_id' => $this->store->id,
            'sku' => 'SKU-LOW',
            'price' => 10, 'cost_price' => 5,
            'stock_quantity' => 2 // Only 2 available
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'customer_id' => $this->customer->id,
            'items' => [['variant_id' => $variant->id, 'quantity' => 5]] // Try to buy 5
        ]);

        $response->assertStatus(500); // Or 422 if you handled the exception with a validation response
        // Expecting Exception message "Insufficient stock"
    }

    public function test_mobile_barcode_scanner_finds_product()
    {
        $product = Product::create(['store_id' => $this->store->id, 'name' => 'Scanned Item']);
        ProductVariant::create([
            'product_id' => $product->id,
            'store_id' => $this->store->id,
            'sku' => 'SKU-SCAN',
            'barcode' => '888555111',
            'price' => 10, 'cost_price' => 5,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/products/find-barcode', [
            'barcode' => '888555111'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Scanned Item']);
    }
}
