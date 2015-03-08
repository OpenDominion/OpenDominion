<?php

if (!function_exists('gamevar')) {
    /**
     * @param  string $key
     * @return mixed
     */
    function gamevar($key)
    {
        return app()['config']->get('gamevars.' . $key);
    }
}
