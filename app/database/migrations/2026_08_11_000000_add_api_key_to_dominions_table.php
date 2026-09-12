<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiKeyToDominionsTable extends Migration
{
    public function up(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->string('api_key', 80)->after('settings')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropUnique(['api_key']);
            $table->dropColumn('api_key');
        });
    }
}
