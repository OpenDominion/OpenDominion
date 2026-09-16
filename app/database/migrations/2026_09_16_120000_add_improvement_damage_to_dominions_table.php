<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImprovementDamageToDominionsTable extends Migration
{
    public function up(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('improvement_damage_science')->default(0)->after('improvement_walls');
            $table->unsignedInteger('improvement_damage_keep')->default(0)->after('improvement_damage_science');
            $table->unsignedInteger('improvement_damage_forges')->default(0)->after('improvement_damage_keep');
            $table->unsignedInteger('improvement_damage_walls')->default(0)->after('improvement_damage_forges');
        });
    }

    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('improvement_damage_science');
            $table->dropColumn('improvement_damage_keep');
            $table->dropColumn('improvement_damage_forges');
            $table->dropColumn('improvement_damage_walls');
        });
    }
}
