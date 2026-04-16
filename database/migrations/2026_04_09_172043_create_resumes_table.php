<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('job_description_id')
                ->constrained('job_descriptions')
                ->onDelete('cascade');
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('candidate_id')    // ← FK in resumes, not in candidates
                ->nullable()
                ->constrained('candidates')
                ->onDelete('set null');

            // File info
            $table->string('original_filename');       // e.g. john_doe_cv.pdf
            $table->string('stored_filename');         // e.g. resumes/uuid.pdf
            $table->enum('file_type', ['pdf', 'docx']);
            $table->unsignedBigInteger('file_size');

            // Processing pipeline
            $table->enum('status', ['uploaded','parsing','parsed','scoring','scored','failed'])
                ->default('uploaded');
            $table->text('raw_text')->nullable();       // extracted raw text
            $table->json('parsed_data')->nullable();    // structured JSON from parser
            $table->text('parse_error')->nullable();    // error message if failed

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};