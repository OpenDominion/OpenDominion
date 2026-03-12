<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\HtmlString;

/**
 * Provides @vite Blade directive support for Laravel 8.
 *
 * Reads the Vite manifest to generate the correct <link> and <script> tags,
 * and supports Vite's dev server (HMR) via the public/hot file.
 */
class ViteHelper
{
    protected static $manifest = null;

    protected static $buildDirectory = 'assets/app';

    public static function tags($entrypoints): HtmlString
    {
        if (!is_array($entrypoints)) {
            $entrypoints = [$entrypoints];
        }

        $tags = '';

        if (static::isRunningHot()) {
            $devUrl = static::devServerUrl();
            $tags .= '<script type="module" src="' . $devUrl . '/@vite/client"></script>';
            foreach ($entrypoints as $entrypoint) {
                $tags .= '<script type="module" src="' . $devUrl . '/' . $entrypoint . '"></script>';
            }
        } else {
            $manifest = static::manifest();
            foreach ($entrypoints as $entrypoint) {
                if (!isset($manifest[$entrypoint])) {
                    continue;
                }
                $chunk = $manifest[$entrypoint];

                // CSS files associated with this JS entry (from CSS imports in JS)
                if (isset($chunk['css'])) {
                    foreach ($chunk['css'] as $css) {
                        $tags .= '<link rel="stylesheet" href="' . asset(static::$buildDirectory . '/' . $css) . '">';
                    }
                }

                $file = $chunk['file'];

                // CSS entry points (e.g., app.scss)
                if (str_ends_with($file, '.css')) {
                    $tags .= '<link rel="stylesheet" href="' . asset(static::$buildDirectory . '/' . $file) . '">';
                } else {
                    $tags .= '<script type="module" src="' . asset(static::$buildDirectory . '/' . $file) . '"></script>';
                }
            }
        }

        return new HtmlString($tags);
    }

    protected static function isRunningHot(): bool
    {
        return file_exists(public_path('hot'));
    }

    protected static function devServerUrl(): string
    {
        $hotFilePath = public_path('hot');
        if (file_exists($hotFilePath)) {
            $url = trim(file_get_contents($hotFilePath));
            if ($url) {
                return rtrim($url, '/');
            }
        }
        return 'http://localhost:5173';
    }

    protected static function manifest(): array
    {
        if (static::$manifest !== null) {
            return static::$manifest;
        }

        // Vite 5+ places manifest at .vite/manifest.json inside the build directory
        $manifestPath = public_path(static::$buildDirectory . '/.vite/manifest.json');
        if (!file_exists($manifestPath)) {
            // Vite 4 places it directly in the build directory
            $manifestPath = public_path(static::$buildDirectory . '/manifest.json');
        }

        if (!file_exists($manifestPath)) {
            static::$manifest = [];
            return [];
        }

        static::$manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
        return static::$manifest;
    }
}
