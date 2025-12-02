<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referral_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('referrers')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->string('status')->default('aberto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_cases');
    }
};
