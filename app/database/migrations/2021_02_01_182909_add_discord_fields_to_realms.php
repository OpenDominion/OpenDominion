<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscordFieldsToRealms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->string('discord_guild_id')->nullable()->after('mixed_alignment');
            $table->string('discord_text_category_channel_id')->nullable()->after('discord_guild_id');
            $table->string('discord_voice_category_channel_id')->nullable()->after('discord_text_category_channel_id');
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->string('discord_role_id')->nullable()->after('name');
            $table->string('discord_text_channel_id')->nullable()->after('discord_role_id');
            $table->string('discord_voice_channel_id')->nullable()->after('discord_text_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn([
                'discord_guild_id',
                'discord_text_category_channel_id',
                'discord_voice_category_channel_id'
            ]);
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn([
                'discord_role_id',
                'discord_text_channel_id',
                'discord_voice_channel_id'
            ]);
        });
    }
}
