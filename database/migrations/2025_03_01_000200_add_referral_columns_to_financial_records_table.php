<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_records', function (Blueprint $table): void {
            $table->foreignId('referrer_id')->nullable()->after('transaction_id')->constrained('referrers')->nullOnDelete();
            $table->foreignId('referral_case_id')->nullable()->after('referrer_id')->constrained('referral_cases')->nullOnDelete();
            $table->foreignId('referral_commission_id')->nullable()->after('referral_case_id')->constrained('referral_commissions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referral_commission_id');
            $table->dropConstrainedForeignId('referral_case_id');
            $table->dropConstrainedForeignId('referrer_id');
        });
    }
};
