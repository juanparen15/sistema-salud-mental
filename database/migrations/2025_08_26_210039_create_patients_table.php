<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->enum('document_type', ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN']);
            $table->string('full_name', 300);
            $table->enum('gender', ['Masculino', 'Femenino', 'Otro']);
            $table->date('birth_date');
            // $table->integer('age')->virtualAs('TIMESTAMPDIFF(YEAR, birth_date, CURDATE())');
            $table->string('phone', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('neighborhood', 200)->nullable();
            $table->string('village', 200)->nullable(); // vereda
            $table->string('eps_code', 100)->nullable();
            $table->string('eps_name', 300)->nullable();
            $table->enum('status', ['active', 'inactive', 'discharged'])->default('active');
            $table->timestamps();

            $table->index(['document_number', 'document_type']);
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
        // Schema::table('patients', function (Blueprint $table) {
        //     // Volver a los tamaños originales
        //     $table->string('phone', 20)->nullable();
        //     $table->string('full_name', 255);
        //     $table->string('address', 255)->nullable();
        //     $table->string('neighborhood', 255)->nullable();
        //     $table->string('village', 255)->nullable();
        //     $table->string('eps_code', 255)->nullable();
        //     $table->string('eps_name', 255)->nullable();
        // });
    }
};
