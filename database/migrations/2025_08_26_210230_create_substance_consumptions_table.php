<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substance_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->datetime('admission_date');
            $table->enum('admission_via', ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA', 'COMUNIDAD']);
            $table->string('diagnosis');
            $table->json('substances_used'); // array de sustancias
            $table->enum('consumption_level', ['Alto Riesgo', 'Riesgo Moderado', 'Bajo Riesgo', 'Perjudicial'])->nullable()->default('Bajo Riesgo');
            $table->text('additional_observation')->nullable();
            $table->enum('status', ['active', 'inactive', 'in_treatment', 'recovered'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('patient_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substance_consumptions');
    }
};