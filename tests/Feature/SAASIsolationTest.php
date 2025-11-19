<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Store;
use App\Models\User;
use App\Models\Product;

class SAASIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    public function test_users_can_only_see_their_own_products()
    {
        // 1. Setup Store A and User A
        $storeA = Store::create(['name' => 'Store A']);
        $userA = User::factory()->create(['store_id' => $storeA->id]);
        Product::factory()->create(['store_id' => $storeA->id, 'name' => 'Product A']);

        // 2. Setup Store B and User B
        $storeB = Store::create(['name' => 'Store B']);
        $userB = User::factory()->create(['store_id' => $storeB->id]);
        Product::factory()->create(['store_id' => $storeB->id, 'name' => 'Product B']);

        // 3. User A requests products
        $response = $this->actingAs($userA)->getJson('/api/products');

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Product A']);
        $response->assertJsonMissing(['name' => 'Product B']); // CRITICAL
    }

    public function test_accounting_entries_are_scoped()
    {
        // Similar setup...
        $storeA = Store::create(['name' => 'Store A']);
        $userA = User::factory()->create(['store_id' => $storeA->id]);
        $productA = Product::factory()->create(['store_id' => $storeA->id, 'name' => 'Product A']);

        // 2. Setup Store B and User B
        $storeB = Store::create(['name' => 'Store B']);
        $userB = User::factory()->create(['store_id' => $storeB->id]);
        $productB = Product::factory()->create(['store_id' => $storeB->id, 'name' => 'Product B']);

        // 3. User A requests products
        
        // User A makes a sale.
        $response = $this->actingAs($userA)->postJson('/api/orders', [
            'customer_id' => $userA->id,
            'items' => [
                ['variant_id' => $productA->variants()->first()->id, 'quantity' => 2],
                ['variant_id' => $productB->variants()->first()->id, 'quantity' => 1]
            ]
        ]);
        $response->assertStatus(201);

        // Check Account 1001 (Cash) for Store A -> Has debit.
        $response->assertJsonFragment(['total_amount' => 100]);

        // Check Account 1001 (Cash) for Store B -> Balance is 0.
        $response->assertJsonFragment(['total_amount' => 0]);
    }
}
