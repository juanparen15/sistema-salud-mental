<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suicide_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->datetime('event_date');
            $table->integer('week_number')->nullable();
            $table->enum('admission_via', ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA', 'COMUNIDAD']);
            $table->integer('attempt_number')->default(1);
            $table->string('benefit_plan')->nullable();
            $table->string('trigger_factor'); // desencadenante
            $table->json('risk_factors'); // factores de riesgo (array)
            $table->text('mechanism'); // mecanismo utilizado
            $table->text('additional_observation')->nullable();
            $table->enum('status', ['active', 'inactive', 'resolved'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('patient_id');
            $table->index('event_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suicide_attempts');
    }
};