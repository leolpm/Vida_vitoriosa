<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 24);
            $table->string('status')->default('active');
            $table->unsignedSmallInteger('task_limit')->nullable();
            $table->timestamps();
            $table->index(['status', 'name']);
        });

        Schema::create('event_team_member', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->primary(['event_id', 'team_member_id']);
        });

        Schema::create('print_flows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('participant_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_member_id')->constrained()->restrictOnDelete();
            $table->string('type')->default('main_print');
            $table->string('status')->default('distributed');
            $table->string('current_step')->default('review');
            $table->foreignId('distributed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('distributed_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'status', 'current_step']);
            $table->index(['team_member_id', 'status']);
            $table->index(['event_id', 'participant_id']);
        });

        Schema::create('print_flow_testimonial', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('print_flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('testimonial_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['print_flow_id', 'testimonial_id']);
            $table->index(['event_id', 'testimonial_id']);
        });

        Schema::create('print_flow_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('print_flow_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->unsignedSmallInteger('max_accesses')->default(1);
            $table->unsignedSmallInteger('accesses_used')->default(0);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->string('invalidation_reason')->nullable();
            $table->timestamps();
            $table->index(['print_flow_id', 'invalidated_at']);
        });

        Schema::create('print_flow_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('print_flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('testimonial_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_member_id')->constrained()->restrictOnDelete();
            $table->string('decision');
            $table->text('rejection_reason')->nullable();
            $table->dateTime('decided_at');
            $table->timestamps();
            $table->index(['event_id', 'testimonial_id', 'decided_at']);
            $table->index(['print_flow_id', 'testimonial_id']);
        });

        Schema::create('print_flow_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('print_flow_id')->constrained()->cascadeOnDelete();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at');
            $table->index(['event_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_flow_audits');
        Schema::dropIfExists('print_flow_reviews');
        Schema::dropIfExists('print_flow_tokens');
        Schema::dropIfExists('print_flow_testimonial');
        Schema::dropIfExists('print_flows');
        Schema::dropIfExists('event_team_member');
        Schema::dropIfExists('team_members');
    }
};
