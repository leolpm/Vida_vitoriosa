<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['participants', 'pdf_batches', 'testimonials'] as $tableName) {
            if (DB::table($tableName)->whereNull('event_id')->exists()) {
                throw new RuntimeException("A tabela {$tableName} possui registros sem evento.");
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('event_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['testimonials', 'pdf_batches', 'participants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('event_id')->nullable()->change();
            });
        }
    }
};
