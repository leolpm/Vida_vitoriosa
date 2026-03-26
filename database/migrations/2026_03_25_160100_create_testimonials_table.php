<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('sender_name');
            $table->string('relationship');
            $table->string('relationship_other')->nullable();
            $table->longText('message');
            $table->string('photo_path')->nullable();
            $table->string('photo_original_name')->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();
            $table->boolean('is_pdf_generated')->default(false);
            $table->timestamp('pdf_generated_at')->nullable();
            $table->foreignId('pdf_batch_id')->nullable()->constrained('pdf_batches')->nullOnDelete();
            $table->string('status')->default('received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
