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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->foreignId('job_description_id')->constrained('job_descriptions')->onDelete('cascade');
            $table->decimal('tfidf_score', 5, 2)->nullable();
            $table->decimal('semantic_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->enum('status', ['shortlisted', 'under_review', 'rejected'])->default('under_review');
            $table->text('ai_summary')->nullable();
            $table->json('questions_json')->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
