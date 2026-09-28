<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAnonymizerFlagsToUserOriginLookupsTable extends Migration
{
    /**
     * IPQS response keys lifted out of the stored JSON into their own columns.
     */
    private const LIFTED_KEYS = ['proxy', 'tor', 'active_vpn', 'active_tor'];

    public function up(): void
    {
        Schema::table('user_origin_lookups', function (Blueprint $table) {
            $table->boolean('proxy')->nullable()->after('city');
            $table->boolean('tor')->nullable()->after('vpn');
            $table->boolean('active_vpn')->nullable()->after('tor');
            $table->boolean('active_tor')->nullable()->after('active_vpn');
        });

        DB::table('user_origin_lookups')
            ->whereNotNull('data')
            ->orderBy('id')
            ->chunkById(500, function ($lookups) {
                foreach ($lookups as $lookup) {
                    $result = json_decode($lookup->data, true);
                    if (!is_array($result)) {
                        continue;
                    }

                    $values = [];
                    foreach (self::LIFTED_KEYS as $key) {
                        if (isset($result[$key])) {
                            $values[$key] = $result[$key];
                        }
                    }

                    if (!empty($values)) {
                        DB::table('user_origin_lookups')->where('id', $lookup->id)->update($values);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('user_origin_lookups', function (Blueprint $table) {
            $table->dropColumn(self::LIFTED_KEYS);
        });
    }
}
