<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_records', function (Blueprint $table): void {
            $table->foreignId('transaction_id')
                ->nullable()
                ->after('attachment_id')
                ->constrained('transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transaction_id');
        });
    }
};
