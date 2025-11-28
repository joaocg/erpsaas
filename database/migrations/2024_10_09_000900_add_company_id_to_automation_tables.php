<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('financial_records', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('medical_appointments', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('medical_exams', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('financial_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('medical_appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('medical_exams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
