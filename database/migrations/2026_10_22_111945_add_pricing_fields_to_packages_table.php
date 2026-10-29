<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('image_path');
            $table->string('discount_type')->nullable()->after('subtotal');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
        });

        DB::table('packages')->update(['subtotal' => DB::raw('price')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_type', 'discount_value']);
        });
    }
};
