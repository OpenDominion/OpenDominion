<?php

use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

class PruneUnactivatedUsers1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new ConsoleOutput();

        DB::transaction(function () use ($output) {

            // First pass: Delete unactivated users
            $output->writeln('Pruning unactivated users');

            $users = DB::table('users')
                ->where('activated', 0)
                ->get();

            $users->map(function ($user) {
                DB::table('user_activities')
                    ->where('user_id', $user->id)
                    ->delete();

                DB::table('users')
                    ->where('id', $user->id)
                    ->delete();
            });

            $output->writeln("Pruned {$users->count()} users");

            // Second pass: Delete all users with no dominions
            $output->writeln('Pruning users with no dominion');

            $usersPrunedCount = 0;

            DB::table('users')
                ->get()
                ->map(function ($user) use (&$usersPrunedCount) {
                    $count = DB::table('dominions')
                        ->where('user_id', $user->id)
                        ->count();

                    if ($count > 0) {
                        return true; // continue
                    }

                    DB::table('user_activities')
                        ->where('user_id', $user->id)
                        ->delete();

                    DB::table('users')
                        ->where('id', $user->id)
                        ->delete();

                    $usersPrunedCount++;
                });

            $output->writeln("Pruned {$usersPrunedCount} users");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // no rollback :^)
    }
}
