<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000002_add_fields_to_permissions_table.php
// ================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('permissions', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
            if (!Schema::hasColumn('permissions', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description', 'category', 'is_system']);
        });
    }
};
