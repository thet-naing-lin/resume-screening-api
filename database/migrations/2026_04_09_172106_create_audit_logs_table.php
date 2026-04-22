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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('action');           // e.g. "resume.uploaded"
            $table->string('target_type')->nullable(); // e.g. "Resume"
            $table->unsignedBigInteger('target_id')->nullable(); // e.g. 5
            $table->json('metadata')->nullable(); // extra context
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
