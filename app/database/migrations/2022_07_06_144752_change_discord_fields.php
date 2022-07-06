<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDiscordFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_discord_users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn([
                'discord_text_category_channel_id',
                'discord_voice_category_channel_id'
            ]);
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->string('discord_category_id')->nullable()->after('discord_role_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_discord_users', function (Blueprint $table) {
            $table->string('email')->change();
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->string('discord_text_category_channel_id')->nullable()->after('discord_guild_id');
            $table->string('discord_voice_category_channel_id')->nullable()->after('discord_text_category_channel_id');
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('discord_category_id');
        });
    }
}
