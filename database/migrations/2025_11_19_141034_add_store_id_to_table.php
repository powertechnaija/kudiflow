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
        $tables = ['users', 'products', 'orders', 'customers', 'chart_of_accounts', 'journal_entries'];
    
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Users might belong to a store, or be super-admins (nullable)
                if ($tableName === 'users') {
                    $table->foreignId('store_id')->nullable()->constrained()->onDelete('set null');
                } else {
                    $table->foreignId('store_id')->constrained()->onDelete('cascade');
                }
            });
        }
        
        // IMPORTANT: Update Unique Constraints for Accounts
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropUnique(['code']); // Drop global unique
            $table->unique(['code', 'store_id']); // Make unique per store
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['users', 'products', 'orders', 'customers', 'chart_of_accounts', 'journal_entries'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropUnique(['code', 'store_id']);
            $table->unique('code');
        });
    }
};
