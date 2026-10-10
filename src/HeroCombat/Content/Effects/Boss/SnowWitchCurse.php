<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

class SnowWitchCurse extends AbstractTelegraph
{
    public function key(): string
    {
        return 'snow_witch_curse';
    }

    public function name(): string
    {
        return 'Snow Witch\'s Curse';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Every other turn, telegraphs a signature move that must be countered with the correct action. Choosing the wrong counter is catastrophic.';
    }

    protected function moves(): array
    {
        return ['bloodrend', 'frost_grip', 'winters_breath'];
    }

    protected function tells(): array
    {
        return [
            'bloodrend' => '{actor} raises her hands slowly, blue veins pulsing beneath frost-white skin.',
            'frost_grip' => 'The temperature plummets. Frost begins to creep up around your feet.',
            'winters_breath' => '{actor} inhales deeply, drawing the mountain\'s frigid air into her lungs.',
        ];
    }
}
