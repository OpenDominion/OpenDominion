<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjectiveTactic;

class AddRaidTacticIdToRaidContributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raid_contributions', function (Blueprint $table) {
            // Add the new raid_tactic_id column
            $table->unsignedInteger('raid_tactic_id')->nullable()->after('raid_objective_id');
        });

        // Migrate existing data to set raid_tactic_id to the first tactic of the same type and objective
        foreach (RaidContribution::all() as $contribution) {
            $firstTactic = RaidObjectiveTactic::where('raid_objective_id', $contribution->raid_objective_id)
                ->where('type', $contribution->type)
                ->orderByDesc('id')
                ->first();
            $contribution->raid_tactic_id = $firstTactic->id;
            $contribution->save();
        }

        Schema::table('raid_contributions', function (Blueprint $table) {
            // Make the column required and add foreign key
            $table->unsignedInteger('raid_tactic_id')->nullable(false)->change();
            $table->foreign('raid_tactic_id')->references('id')->on('raid_objective_tactics');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raid_contributions', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign('raid_contributions_raid_tactic_id_foreign');

            // Drop the column
            $table->dropColumn('raid_tactic_id');
        });
    }
}
