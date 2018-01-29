<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

class AddRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Role::create(['name' => 'Developer']);
        Role::create(['name' => 'Administrator']);
        Role::create(['name' => 'Moderator']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Role::where('name', 'Developer')->delete();
        Role::where('name', 'Administrator')->delete();
        Role::where('name', 'Moderator')->delete();
    }
}
