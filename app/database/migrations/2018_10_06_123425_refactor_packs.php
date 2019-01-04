<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorPacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->unsignedInteger('creator_dominion_id')
                ->after('realm_id')
                ->default(0); // we make it a FK later, set default 0 for now
        });

        $userIdsByRoundId = [];

        foreach (DB::table('packs')->get() as $pack) {
            if (!isset($userIdsByRoundId[$pack->round_id])) {
                $userIdsByRoundId[$pack->round_id] = [];
            }

            $userIdsByRoundId[$pack->round_id][] = $pack->user_id;
        }

        foreach ($userIdsByRoundId as $roundId => $userIds) {
            foreach ($userIds as $userId) {
                $dominionId = DB::table('dominions')->where([
                    'user_id' => $userId,
                    'round_id' => $roundId,
                ])->first()->id;

                DB::table('packs')->where([
                    'round_id' => $roundId,
                    'user_id' => $userId,
                ])->update([
                    'creator_dominion_id' => $dominionId,
                ]);
            }
        }

        Schema::table('packs', function (Blueprint $table) {
            // Drop foreign keys
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['round_id']);
                $table->dropForeign(['user_id']);
                $table->dropForeign(['realm_id']);
            }

            // Drop unique keys
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropUnique(['round_id', 'user_id']);
                $table->dropUnique(['user_id', 'round_id']);
                $table->dropUnique(['name', 'round_id']);
                $table->dropUnique(['password', 'round_id', 'name']);
            }

            // Drop user_id
            $table->dropColumn('user_id');

            // Add unique indexes
            $table->unique(['round_id', 'name']);
            $table->unique(['creator_dominion_id', 'round_id']);

            // Add foreign keys
            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('creator_dominion_id')->references('id')->on('dominions');
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn([
                'has_pack',
                'reserved_slots',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->boolean('has_pack')->default(false);
            $table->unsignedInteger('reserved_slots')->default(0);
        });

        Schema::table('packs', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['creator_dominion_id']);
                $table->dropUnique(['creator_dominion_id', 'round_id']);
            }

            $table->unsignedInteger('user_id')->default(0);
        });

        $realmIds = [];

        foreach (DB::table('packs')->get() as $pack) {
            $dominion = DB::table('dominions')->where([
                'id' => $pack->creator_dominion_id,
            ])->first();

            if (!in_array($dominion->realm_id, $realmIds, true)) {
                $realmIds[] = $dominion->realm_id;
            }

            DB::table('packs')->where([
                'round_id' => $dominion->round_id,
                'creator_dominion_id' => $dominion->id,
            ])->update([
                'user_id' => $dominion->user_id,
            ]);
        }

        foreach ($realmIds as $realmId) {
            DB::table('realms')->where([
                'id' => $realmId,
            ])->update([
                'has_pack' => true,
                'reserved_slots' => 0, // eh cba
            ]);
        }

        Schema::table('packs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('realm_id')->references('id')->on('realms');

            $table->dropColumn('creator_dominion_id');
        });
    }
}
