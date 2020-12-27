<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;

class CreateRealmWarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realm_wars', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('source_realm_id')->unsigned();
            $table->string('source_realm_name')->nullable();
            $table->integer('target_realm_id')->unsigned();
            $table->string('target_realm_name')->nullable();
            $table->timestamp('active_at')->nullable();
            $table->timestamp('inactive_at')->nullable();
            $table->timestamps();

            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('target_realm_id')->references('id')->on('realms');
        });

        foreach (Realm::all() as $realm) {
            if ($realm->war_realm_id !== null) {
                $warRealm = Realm::find($realm->war_realm_id);
                DB::table('realm_wars')->insert([
                    'source_realm_id' => $realm->id,
                    'source_realm_name' => $realm->name,
                    'target_realm_id' => $warRealm->id,
                    'target_realm_name' => $warRealm->name,
                    'active_at' => $realm->war_active_at,
                    'inactive_at' => null
                ]);
            }
        }

        Schema::table('realms', function (Blueprint $table) {
            $table->dropForeign('realms_war_realm_id_foreign');
            $table->dropColumn('war_realm_id');
            $table->dropColumn('war_active_at');
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
            $table->integer('war_realm_id')->after('name')->unsigned()->nullable();
            $table->timestamp('war_active_at')->after('war_realm_id')->nullable();

            $table->foreign('war_realm_id')->references('id')->on('realms');
        });

        foreach (RealmWar::all() as $war) {
            if ($war->active_at < now() && ($war->inactive_at == null || $war->inactive_at < now())) {
                $war->sourceRealm->war_realm_id = $war->target_realm_id;
                $war->sourceRealm->war_active_at = $war->active_at;
                $war->sourceRealm->save();
            }
        }

        Schema::dropIfExists('realm_wars');
    }
}
