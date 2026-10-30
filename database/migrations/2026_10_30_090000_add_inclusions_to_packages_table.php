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
        Schema::table('packages', function (Blueprint $table) {
            // What the franchise partner gets besides the products, e.g. canopy and T-shirt; null until first set.
            $table->json('inclusions')->nullable()->after('description');
            // The value of the goods promised for the package price, shown as "₹9,000 worth of goods".
            $table->decimal('goods_worth', 10, 2)->nullable()->after('fixed_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['inclusions', 'goods_worth']);
        });
    }
};
