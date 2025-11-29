<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
            $table->string('exam_type');
            $table->string('lab_name')->nullable();
            $table->date('occurred_on')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('results_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_exams');
    }
};
