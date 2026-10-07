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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('franchise_holder')->after('password');
            $table->string('status')->default('pending')->after('role');
            $table->foreignId('sales_manager_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('phone')->nullable()->after('sales_manager_id');
            $table->string('business_name')->nullable()->after('phone');
            $table->string('address')->nullable()->after('business_name');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('pincode')->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_manager_id');
            $table->dropColumn(['role', 'status', 'phone', 'business_name', 'address', 'city', 'state', 'pincode']);
        });
    }
};
