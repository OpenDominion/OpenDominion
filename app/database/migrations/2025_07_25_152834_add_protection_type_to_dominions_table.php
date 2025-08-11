<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProtectionTypeToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->string('protection_type')->nullable()->after('abandoned_at');
            $table->integer('protection_ticks')->default(72)->after('protection_type');
            $table->boolean('protection_finished')->default(false)->after('protection_ticks_remaining');
        });

        // Update existing dominions to set protection_finished = true where protection_ticks_remaining = 0
        DB::table('dominions')
            ->where('protection_ticks_remaining', 0)
            ->where('protection_finished', false)
            ->update(['protection_finished' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn(['protection_type', 'protection_ticks', 'protection_finished']);
        });
    }
}
