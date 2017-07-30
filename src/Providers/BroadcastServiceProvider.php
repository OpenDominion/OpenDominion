<?php

namespace OpenDominion\Providers;

use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends AbstractServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes();

        require base_path('app/routes/channels.php');
    }
}
