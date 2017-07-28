<?php

namespace OpenDominion\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use OpenDominion\Models\Guest;
use Pseudo\Contracts\GuestContract;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'OpenDominion\Model' => 'OpenDominion\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }

    public function register()
    {
        // Bind a guest user for visitors.
        $this->app->bind(GuestContract::class, Guest::class);
    }
}
