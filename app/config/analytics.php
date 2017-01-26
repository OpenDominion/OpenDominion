<?php

return [

    /**
     * current selected provider
     */
    'provider' => env('ANALYTICS_PROVIDER', 'GoogleAnalytics'),

    /**
     * configurations for all possible providers
     */
    'configurations' => [

        /**
         * The Google Analytics provider supports the following properties:
         * - tracking_id (string)
         * - tracking_domain (string:auto) - default will be 'auto' if config property not exists
         * - tracker_name (string:t0) - default will be 't0' if config property not exists
         * - display_features (bool) - default will be false if no config property exists
         * - anonymize_ip (bool) - default will be false if no config property exists
         * - auto_track (bool) - default will be false if no config property exists
         * - debug (bool) - default will be false if no config property exists
         */
        'GoogleAnalytics' => [

            /**
             * Tracking ID
             */
            'tracking_id' => env('ANALYTICS_TRACKING_ID'),

            /**
             * Tracking Domain
             */
            'tracking_domain' => env('ANALYTICS_TRACKING_DOMAIN', 'auto'),

            /**
             * Tracker Name
             */
            'tracker_name' => env('ANALYTICS_TRACKER_NAME', 't0'),

            /**
             * enabling the display feature plugin
             */
            'display_features' => env('ANALYTICS_DISPLAY_FEATURES', false),

            /**
             * Use ip anonymized
             */
            'anonymize_ip' => env('ANALYTICS_ANONYMIZE_IP', true),

            /**
             * Auto tracking pageview: ga('send', 'pageview');
             * If false, you have to do it manually for each request
             * Or you can use Analytics::disableAutoTracking(), Analytics::enableAutoTracking()
             */
            'auto_track' => env('ANALYTICS_AUTO_TRACK', true),

            /**
             * Enable the debugging version of Google Analytics
             */
            'debug' => env('ANALYTIS_DEBUG', env('APP_ENV') === 'local'),
        ]

    ],

];
