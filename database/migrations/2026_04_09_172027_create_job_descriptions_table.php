<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // who created it
            $table->string('title');
            $table->text('description');
            $table->json('required_skills');           // ["PHP", "Laravel", "MySQL"]
            $table->enum('experience_level', ['junior', 'mid', 'senior']);
            $table->enum('employment_type', ['full-time', 'part-time', 'contract', 'internship']);
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_descriptions');
    }
};
