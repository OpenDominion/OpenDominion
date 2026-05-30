<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddHomepageFieldsToMessageBoardThreadsTable extends Migration
{
    public function up(): void
    {
        Schema::table('message_board_threads', function (Blueprint $table) {
            $table->boolean('homepage_display')->default(false)->after('flagged_by');
            $table->string('homepage_preset', 20)->nullable()->after('homepage_display');
            $table->string('homepage_subtitle', 255)->nullable()->after('homepage_preset');
            $table->string('homepage_url', 255)->nullable()->after('homepage_subtitle');
        });

        DB::table('message_board_categories')->updateOrInsert(
            ['slug' => 'announcements'],
            ['name' => 'Announcements', 'role_required' => 'Administrator']
        );
    }

    public function down(): void
    {
        Schema::table('message_board_threads', function (Blueprint $table) {
            $table->dropColumn(['homepage_display', 'homepage_preset', 'homepage_subtitle', 'homepage_url']);
        });

        DB::table('message_board_categories')->where('slug', 'announcements')->delete();
    }
}
