<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class SAASIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed Roles
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }
    }

    public function test_users_can_only_see_their_own_products()
    {
        // 1. Setup Store A
        $storeA = Store::factory()->create(['name' => 'Store A']);
        $userA = User::factory()->create(['store_id' => $storeA->id]);
        $userA->assignRole('admin');
        $productA = Product::factory()->create(['store_id' => $storeA->id, 'name' => 'Product A']);

        // 2. Setup Store B
        $storeB = Store::factory()->create(['name' => 'Store B']);
        $userB = User::factory()->create(['store_id' => $storeB->id]);
        $userB->assignRole('admin');
        $productB = Product::factory()->create(['store_id' => $storeB->id, 'name' => 'Product B']);

        // 3. Act: User A requests products
        $response = $this->actingAs($userA)->getJson('/api/products');

        // 4. Assert: Sees A, Doesn't see B
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Product A'])
                 ->assertJsonMissing(['name' => 'Product B']);
    }

    public function test_sku_can_be_duplicated_across_different_stores()
    {
        // Scenario: Both stores want to use SKU "SHIRT-001"
        
        // Store A
        $storeA = Store::factory()->create(['name' => 'Store A']);
        $userA = User::factory()->create(['store_id' => $storeA->id]);
        $userA->assignRole('admin');

        $this->actingAs($userA)->postJson('/api/products', [
            'store_id' => $storeA->id,
            'name' => 'T-Shirt A',
            'variants' => [[
                'sku' => 'SHIRT-001', // Taken by A
                'price' => 100, 'cost_price' => 50
            ]]
        ])->assertStatus(201);

        // Store B
        $storeB = Store::factory()->create(['name' => 'Store B']);
        $userB = User::factory()->create(['store_id' => $storeB->id]);
        $userB->assignRole('admin');

        // Act: Store B tries to use "SHIRT-001"
        $response = $this->actingAs($userB)->postJson('/api/products', [
            'store_id' => $storeB->id,
            'name' => 'T-Shirt B',
            'variants' => [[
                'sku' => 'SHIRT-001', // Should be allowed!
                'price' => 120, 'cost_price' => 60
            ]]
        ]);

        // Assert: Success (201) instead of Validation Error (422)
        $response->assertStatus(201);
    }

    public function test_sku_cannot_be_duplicated_within_same_store()
    {
        $storeA = Store::factory()->create(['name' => 'Store A']);
        $userA = User::factory()->create(['store_id' => $storeA->id]);
        $userA->assignRole('admin');

        // Create first product
        $this->actingAs($userA)->postJson('/api/products', [
            'store_id' => $storeA->id,
            'name' => 'Item 1',
            'variants' => [['sku' => 'UNIQUE-123', 'price' => 10, 'cost_price' => 5]]
        ]);

        // Try creating second product with same SKU
        $response = $this->actingAs($userA)->postJson('/api/products', [
            'store_id' => $storeA->id,
            'name' => 'Item 2',
            'variants' => [['sku' => 'UNIQUE-123', 'price' => 20, 'cost_price' => 10]]
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['variants.0.sku']);
    }
}
