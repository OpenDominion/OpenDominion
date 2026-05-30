<?php

namespace OpenDominion\Helpers;

final class AnnouncementPresetHelper
{
    public const DEFAULT_PRESET = 'announcement';

    public const PRESETS = [
        'announcement' => ['label' => 'Announcement', 'icon' => 'fa-bullhorn',       'cssClass' => 'chronicle-entry-announcement'],
        'round'        => ['label' => 'Round',        'icon' => 'fa-shield-halved', 'cssClass' => 'chronicle-entry-round'],
        'patch'        => ['label' => 'Patch Notes',  'icon' => 'fa-hammer',        'cssClass' => 'chronicle-entry-patch'],
        'event'        => ['label' => 'Event',        'icon' => 'fa-fire',          'cssClass' => 'chronicle-entry-event'],
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::PRESETS);
    }

    /**
     * @return array{label: string, icon: string, cssClass: string}
     */
    public static function get(?string $key): array
    {
        return self::PRESETS[$key] ?? self::PRESETS[self::DEFAULT_PRESET];
    }
}
