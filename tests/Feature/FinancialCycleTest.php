<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ChartOfAccount;
use App\Models\Store;
use App\Models\User;
use App\Models\Customer;
use App\Constants\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class FinancialCycleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $variant;
    protected $store;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->store = Store::factory()->create();
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
        // Create roles
        if (!Role::where('name', 'admin')->where('store_id', $this->store->id)->exists()) {
            $role = Role::create(['name' => 'admin', 'store_id' => $this->store->id]);
        }

        $this->user = User::factory()->create(['store_id' => $this->store->id]);
        $this->user->assignRole('admin');

        // Seed opening balance to create accounts
        $this->actingAs($this->user)->postJson('/api/setup/opening-balance', [
            'cash_on_hand' => 50000,
            'inventory_value' => 0,
            'date' => now()->toDateString()
        ]);

        // 2. Create Product with 0 stock
        $product = Product::create(['store_id' => $this->user->store_id, 'name' => 'Test Item']);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'store_id' => $this->store->id,
            'sku' => 'TEST-1',
            'price' => 200.00, // Selling Price
            'cost_price' => 100.00, // Cost
            'stock_quantity' => 0
        ]);
    }

    public function test_full_sales_and_return_cycle_ledger_integrity()
    {
        // STEP 1: PROCUREMENT (Buy 10 items)
        // We assume we pay via Cash for simplicity
        $this->actingAs($this->user)->postJson('/api/inventory/purchase', [
            'supplier_name' => 'Supplier X',
            'payment_method' => 'cash',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 10, 'unit_cost' => 100.00]]
        ])->assertStatus(201);

        // Check Inventory Asset Account (Code 1200) -> Should be Debit 1000 (10 * 100)
        $invAssetId = ChartOfAccount::where('code', '1200')->where('store_id', $this->store->id)->first()->id;
        $this->assertDatabaseHas('journal_items', [
            'chart_of_account_id' => $invAssetId,
            'debit' => 1000.00
        ]);

        // STEP 2: MAKE A SALE (Sell 1 item)
        // Revenue: 200, Cost: 100
        $saleResponse = $this->actingAs($this->user)->postJson('/api/orders', [
            'customer_id' => $this->customer->id,
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 1]]
        ]);
        $saleResponse->assertStatus(201);
        $orderId = $saleResponse->json('id');

        // Verify Ledger for Sale
        // 1. Revenue (Credit 200)
        $revId = ChartOfAccount::where('code', '4000')->where('store_id', $this->store->id)->first()->id;
        $this->assertDatabaseHas('journal_items', ['chart_of_account_id' => $revId, 'credit' => 200.00]);
        
        // 2. COGS (Debit 100)
        $cogsId = ChartOfAccount::where('code', '5000')->where('store_id', $this->store->id)->first()->id;
        $this->assertDatabaseHas('journal_items', ['chart_of_account_id' => $cogsId, 'debit' => 100.00]);

        // STEP 3: PROCESS RETURN
        $this->actingAs($this->user)->postJson('/api/returns', [
            'order_id' => $orderId,
            'reason' => 'Defective',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 1]]
        ])->assertStatus(201);

        // Verify Ledger Reversal
        // Sales Returns (Debit 200)
        $returnId = ChartOfAccount::where('code', '4100')->where('store_id', $this->store->id)->first()->id;
        $this->assertDatabaseHas('journal_items', ['chart_of_account_id' => $returnId, 'debit' => 200.00]);
    }
}
