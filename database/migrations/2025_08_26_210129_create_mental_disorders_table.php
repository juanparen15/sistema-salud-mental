<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mental_disorders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->datetime('admission_date');
            $table->enum('admission_type', ['AMBULATORIO', 'HOSPITALARIO', 'URGENCIAS']);
            $table->enum('admission_via', ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA']);
            $table->string('service_area')->nullable();
            $table->string('diagnosis_code', 10);
            $table->string('diagnosis_description');
            $table->datetime('diagnosis_date');
            $table->enum('diagnosis_type', ['Diagnostico Principal', 'Diagnostico Relacionado']);
            $table->text('additional_observation')->nullable();
            $table->enum('status', ['active', 'inactive', 'discharged'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('patient_id');
            $table->index('diagnosis_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mental_disorders');
    }
};