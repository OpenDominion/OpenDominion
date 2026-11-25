<?php

namespace OpenDominion\Providers;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\ProtectionService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
//        'OpenDominion\Model' => 'OpenDominion\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('viewOpCenter', function (User $user, Dominion $dominion): Response {
            if ($dominion->user_id !== $user->id) {
                return Response::deny('Select your own dominion to access the op center.');
            }

            if ($dominion->locked_at !== null) {
                return Response::deny('Locked dominions are not allowed access to the op center.');
            }

            if ($dominion->round->hasStarted() && app(ProtectionService::class)->isUnderProtection($dominion)) {
                return Response::deny('Dominions in protection are not allowed access to the op center.');
            }

            return Response::allow();
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdministrator();
        });
    }
}
