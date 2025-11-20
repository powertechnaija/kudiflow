<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use Spatie\Permission\Models\Role;
use App\Services\AccountingService;

class BalanceSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a store and user
        $store = Store::factory()->create();
        $user = User::factory()->create(['store_id' => $store->id]);

        // Assign roles
        $role = Role::create(['name' => 'admin', 'store_id' => $store->id]);
        $user->assignRole($role);
        
        $this->actingAs($user);

        // Set up opening balance
        $accounting = $this->app->make(AccountingService::class);
        $accounting->createOpeningBalanceEntry(
            $store->id,
            now(),
            50000, // Cash
            500    // Inventory
        );
    }

    /**
     * Test if the balance sheet equation (Assets = Liabilities + Equity) holds true.
     */
    public function test_balance_sheet_equation_holds_true()
    {
        $response = $this->getJson('/api/reports/balance-sheet');

        $response->assertStatus(200);
        
        $data = $response->json();

        $this->assertEquals(50500, $data['assets']);
        $this->assertEquals(50500, $data['check']['liabilities_plus_equity']);
        $this->assertTrue($data['check']['is_balanced']);
    }
}
