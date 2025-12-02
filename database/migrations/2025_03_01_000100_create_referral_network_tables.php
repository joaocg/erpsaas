<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employeeship_id')->nullable()->constrained('employeeships')->nullOnDelete();
            $table->string('name');
            $table->string('document')->nullable();
            $table->string('type')->default('referrer');
            $table->decimal('default_commission_percentage', 8, 2)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('referrer_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('referrers')->nullOnDelete();
            $table->foreignId('child_id')->constrained('referrers')->cascadeOnDelete();
            $table->decimal('commission_percentage', 8, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['parent_id', 'child_id']);
        });

        Schema::create('referral_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('referrers')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('office_lawyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('case_value', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->date('contract_date')->nullable();
            $table->date('expected_payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_case_id')->constrained('referral_cases')->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('referrers')->cascadeOnDelete();
            $table->unsignedInteger('level')->default(0);
            $table->decimal('commission_percentage', 8, 2)->nullable();
            $table->decimal('commission_value', 15, 2)->default(0);
            $table->foreignId('financial_record_id')->nullable()->constrained('financial_records')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referral_cases');
        Schema::dropIfExists('referrer_relations');
        Schema::dropIfExists('referrers');
    }
};
