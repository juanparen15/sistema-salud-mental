<?php

// ================================
// ARCHIVO: database/migrations/2024_01_01_000001_add_fields_to_roles_table.php
// ================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('roles', 'color')) {
                $table->string('color', 7)->default('#6366f1')->after('description');
            }
            if (!Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description', 'color', 'is_active']);
        });
    }
};
