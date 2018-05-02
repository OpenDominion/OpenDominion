<?php

namespace OpenDominion\Extensions\Markdown;

use League\CommonMark\Extension\Extension;
use League\CommonMark\Inline\Element\Image;

class ResponsiveImageExtension extends Extension
{
    /** @var ResponsiveImageRenderer */
    protected $renderer;

    /**
     * ResponsiveImageExtension constructor.
     *
     * @param ResponsiveImageRenderer $renderer
     */
    public function __construct(ResponsiveImageRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getInlineRenderers(): array
    {
        return [Image::class => $this->renderer];
    }
}
