<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_cases', function (Blueprint $table): void {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('client_id')
                ->constrained('invoices')
                ->nullOnDelete();
        });

        Schema::table('referral_commissions', function (Blueprint $table): void {
            $table->foreignId('bill_id')
                ->nullable()
                ->after('referrer_id')
                ->constrained('bills')
                ->nullOnDelete();

            $table->foreignId('transaction_id')
                ->nullable()
                ->after('financial_record_id')
                ->constrained('transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referral_commissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropConstrainedForeignId('bill_id');
        });

        Schema::table('referral_cases', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
