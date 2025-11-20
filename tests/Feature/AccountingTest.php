<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Store;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    protected $store;
    protected $user;
    protected $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::factory()->create();
        $this->user = User::factory()->create(['store_id' => $this->store->id]);
        $this->accountingService = $this->app->make(AccountingService::class);
    }

    public function test_unbalanced_entry_throws_error()
    {
        $this->expectException(\Exception::class);

        $this->accountingService->recordEntry($this->store->id, now(), 'Test', 'REF001', [
            ['chart_of_account_id' => 1, 'debit' => 100, 'credit' => 0], // Missing credit side
        ]);
    }

    public function test_balanced_entry_is_recorded()
    {
        $cash = ChartOfAccount::factory()->create(['type' => 'Asset', 'store_id' => $this->store->id]);
        $revenue = ChartOfAccount::factory()->create(['type' => 'Revenue', 'store_id' => $this->store->id]);

        $entry = $this->accountingService->recordEntry($this->store->id, now(), 'Sale', 'REF002', [
            ['chart_of_account_id' => $cash->id, 'debit' => 100, 'credit' => 0], // Cash (Asset)
            ['chart_of_account_id' => $revenue->id, 'debit' => 0, 'credit' => 100], // Revenue (Income)
        ]);

        $this->assertDatabaseHas('journal_entries', ['reference_number' => 'REF002']);
    }
}
