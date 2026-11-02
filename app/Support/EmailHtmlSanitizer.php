<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class EmailHtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'p', 'br', 'div', 'span', 'strong', 'b', 'em', 'i', 'u',
        'ul', 'ol', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'pre', 'code',
        'sub', 'sup',
    ];

    private const DROP_TAGS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'form', 'input', 'button', 'textarea', 'select', 'option',
        'svg', 'math', 'video', 'audio', 'source', 'link', 'meta', 'base',
    ];

    public function sanitize(string $html): string
    {
        if ($html === '' || ! class_exists(DOMDocument::class)) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8"><div id="email-content">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $container = $document->getElementById('email-content');

            if (! $container) {
                return '';
            }

            $this->cleanChildren($container);

            $result = '';
            foreach ($container->childNodes as $child) {
                $result .= $document->saveHTML($child);
            }

            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $parent->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_TAGS, true)) {
                $parent->removeChild($child);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->cleanChildren($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
            $title = $child->getAttribute('title');
            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }

            if ($tag === 'a') {
                $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
                if (in_array($scheme, ['http', 'https', 'mailto'], true)) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }

            if ($title !== '') {
                $child->setAttribute('title', $title);
            }

            $this->cleanChildren($child);
        }
    }
}
