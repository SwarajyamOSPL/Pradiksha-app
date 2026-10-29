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
        Schema::table('orders', function (Blueprint $table) {
            // Saved by the admin once the order has been dispatched.
            $table->string('courier_name')->nullable()->after('notes');
            $table->string('awb_number')->nullable()->after('courier_name');
            $table->string('tracking_url', 2048)->nullable()->after('awb_number');
            $table->timestamp('dispatched_at')->nullable()->after('tracking_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_name', 'awb_number', 'tracking_url', 'dispatched_at']);
        });
    }
};
