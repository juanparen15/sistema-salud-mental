<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_followups', function (Blueprint $table) {
            $table->id();
            $table->morphs('followupable'); // Para polimorfismo con los 3 tipos
            $table->date('followup_date');
            $table->integer('year');
            $table->integer('month');
            $table->text('description');
            $table->enum('status', ['pending', 'completed', 'not_contacted', 'refused'])->default('pending');
            $table->date('next_followup')->nullable();
            $table->json('actions_taken')->nullable(); // array de acciones
            $table->json('source_reference')->nullable(); // Para referencias adicionales
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['followupable_id', 'followupable_type', 'year', 'month'], 'unique_followup');
            $table->index(['year', 'month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_followups');
    }
};
