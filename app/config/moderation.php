<?php

return [
    /*
     * Regex patterns that strongly suggest an XSS / script-injection attempt.
     * A match auto-flags the post for moderator review (it never appears in threads).
     * False positives are acceptable here; a moderator can unflag.
     */
    'xss_patterns' => [
        '/<script\b/i',
        '/javascript\s*:/i',
        '/\bon\w+\s*=\s*["\']?[^"\'\s>]+/i',
        '/<iframe\b/i',
        '/<embed\b/i',
        '/<object\b/i',
        '/<svg\b/i',
        '/data\s*:\s*text\/html/i',
    ],

    /*
     * Comma-separated list of words that auto-flag a post for moderator review.
     * Matched with word boundaries (case-insensitive) to avoid Scunthorpe-style
     * false positives. Curate via the MODERATION_FLAGGED_WORDS env var so the
     * list itself stays out of git.
     */
    'flagged_words' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('MODERATION_FLAGGED_WORDS', ''))
    ))),
];
