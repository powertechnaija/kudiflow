<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccountingTest extends TestCase
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
    public function test_unbalanced_entry_throws_error()
    {
        $service = new AccountingService();
        
        $this->expectException(\Exception::class);

        $service->recordEntry(now(), 'Test', 'REF001', [
            ['chart_of_account_id' => 1, 'debit' => 100, 'credit' => 0], // Missing credit side
        ]);
    }

    public function test_balanced_entry_is_recorded()
    {
        $cash = ChartOfAccount::factory()->create(['type' => 'Asset']);
        $revenue = ChartOfAccount::factory()->create(['type' => 'Revenue']);

        $service = new AccountingService();
        
        $entry = $service->recordEntry(now(), 'Sale', 'REF002', [
            ['chart_of_account_id' => $cash->id, 'debit' => 100, 'credit' => 0], // Cash (Asset)
            ['chart_of_account_id' => $revenue->id, 'debit' => 0, 'credit' => 100], // Revenue (Income)
        ]);

        $this->assertDatabaseHas('journal_entries', ['reference_number' => 'REF002']);
    }
}
