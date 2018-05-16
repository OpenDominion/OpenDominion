<?php

namespace OpenDominion\Extensions\Markdown;

use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Renderer\ImageRenderer;

class ResponsiveImageRenderer extends ImageRenderer
{
    /**
     * {@inheritdoc}
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer)
    {
        $element = parent::render($inline, $htmlRenderer);

        $element->setAttribute('class', 'img-responsive');

        return $element;
    }
}
