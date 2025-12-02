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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('client_id')->constrained('partners')->nullOnDelete();
            $table->decimal('commission_percent', 8, 2)->nullable()->after('partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn(['partner_id', 'commission_percent']);
        });
    }
};
