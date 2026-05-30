<?php

namespace OpenDominion\Services;

class ContentModerationService
{
    /**
     * Returns true if the given content should be auto-flagged for moderator review.
     */
    public function shouldFlag(string $content): bool
    {
        if ($content === '') {
            return false;
        }

        foreach ((array) config('moderation.xss_patterns', []) as $pattern) {
            if (@preg_match($pattern, $content) === 1) {
                return true;
            }
        }

        $words = (array) config('moderation.flagged_words', []);
        if (!empty($words)) {
            $escaped = array_map(static fn (string $w): string => preg_quote($w, '/'), $words);
            $pattern = '/\b(?:' . implode('|', $escaped) . ')\b/iu';
            if (@preg_match($pattern, $content) === 1) {
                return true;
            }
        }

        return false;
    }
}
