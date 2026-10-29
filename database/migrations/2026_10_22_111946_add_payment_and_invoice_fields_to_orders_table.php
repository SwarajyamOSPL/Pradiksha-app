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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('razorpay_payment_id');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_amount');
            $table->boolean('tax_inclusive')->default(true)->after('tax_rate');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_inclusive');
            $table->string('invoice_number')->nullable()->unique()->after('total_amount');
            $table->timestamp('paid_at')->nullable()->after('invoice_number');
        });

        DB::table('orders')->update(['subtotal' => DB::raw('total_amount')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'subtotal', 'discount_amount', 'tax_rate', 'tax_inclusive', 'tax_amount', 'invoice_number', 'paid_at',
            ]);
        });
    }
};
