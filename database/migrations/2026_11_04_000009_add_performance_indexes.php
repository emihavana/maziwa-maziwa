<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->index('route_id', 'farmers_route_id_index');
        });

        Schema::table('milk_collections', function (Blueprint $table) {
            $table->unique(['farmer_id', 'collection_date'], 'milk_collections_farmer_date_unique');
            $table->index(['collector_id', 'collection_date'], 'milk_collections_collector_date_index');
            $table->index(['route_id', 'collection_date'], 'milk_collections_route_date_index');
            $table->index('collection_date', 'milk_collections_date_index');
        });

        Schema::table('milk_prices', function (Blueprint $table) {
            $table->index(['route_id', 'effective_date', 'is_active'], 'milk_prices_route_effective_active_index');
            $table->index('effective_date', 'milk_prices_effective_date_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date', 'sales_sale_date_index');
        });

        Schema::table('sale_prices', function (Blueprint $table) {
            $table->index(['effective_date', 'is_active'], 'sale_prices_effective_active_index');
        });

        Schema::table('farmer_debt_transactions', function (Blueprint $table) {
            $table->index(['farmer_id', 'transaction_date'], 'farmer_debt_transactions_farmer_date_index');
            $table->index(['transaction_type', 'transaction_date'], 'farmer_debt_transactions_type_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->dropIndex('farmers_route_id_index');
        });

        Schema::table('milk_collections', function (Blueprint $table) {
            $table->dropUnique('milk_collections_farmer_date_unique');
            $table->dropIndex('milk_collections_collector_date_index');
            $table->dropIndex('milk_collections_route_date_index');
            $table->dropIndex('milk_collections_date_index');
        });

        Schema::table('milk_prices', function (Blueprint $table) {
            $table->dropIndex('milk_prices_route_effective_active_index');
            $table->dropIndex('milk_prices_effective_date_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_sale_date_index');
        });

        Schema::table('sale_prices', function (Blueprint $table) {
            $table->dropIndex('sale_prices_effective_active_index');
        });

        Schema::table('farmer_debt_transactions', function (Blueprint $table) {
            $table->dropIndex('farmer_debt_transactions_farmer_date_index');
            $table->dropIndex('farmer_debt_transactions_type_date_index');
        });
    }
};
