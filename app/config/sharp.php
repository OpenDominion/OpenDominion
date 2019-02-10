<?php

return [

    'auth' => [
        'check_handler' => \OpenDominion\Sharp\Auth\SharpCheckHandler::class,
        'guard' => 'web',

        'login_attribute' => 'email',
        'password_attribute' => 'password',
        'display_attribute' => 'display_name',
    ],

    'entities' => [

//        'dominion' => [
//            'list' => \OpenDominion\Sharp\Entities\Dominion\DominionSharpList::class,
//            'form' => \OpenDominion\Sharp\Entities\Dominion\DominionSharpForm::class,
//            'validator' => \OpenDominion\Sharp\DominionSharpValidator::class,
//            'policy' => \OpenDominion\Sharp\Policies\DominionPolicy::class
//        ],

        'user' => [
            'list' => \OpenDominion\Sharp\Entities\User\UserSharpList::class,
            'form' => \OpenDominion\Sharp\Entities\User\UserSharpForm::class,
            'validator' => null,
            'policy' => null,
        ],

    ],

    'menu' => [
        [
            'label' => 'Game',
            'entities' => [
                [
                    'label' => 'Dominions',
                    'entity' => 'dominion',
                ],
            ],
        ],
        [
            'label' => 'Admin',
            'entities' => [
                [
                    'label' => 'Users',
                    'icon' => 'fa-user',
                    'entity' => 'user',
                ],
            ],
        ]
    ],

];
