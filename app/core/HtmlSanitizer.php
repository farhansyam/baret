<?php
declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

/** Penyaring HTML dari editor berita (whitelist tag & atribut). */
final class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'a'];
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $root = $doc->getElementById('__root');
        if (!$root) {
            return e(strip_tags($html));
        }
        self::walk($root);
        self::wrapInline($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Rapikan paragraf kosong dari contenteditable
        $out = preg_replace('#<p>(\s|&nbsp;|<br>)*</p>#u', '', $out);
        return trim((string) $out);
    }

    /** Teks/elemen inline di level teratas dibungkus <p> agar tipografi konsisten. */
    private static function wrapInline(DOMElement $root): void
    {
        $blocks = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote'];
        $doc = $root->ownerDocument;
        $p = null;
        foreach (iterator_to_array($root->childNodes) as $child) {
            $isBlock = $child instanceof DOMElement && in_array(strtolower($child->tagName), $blocks, true);
            if ($isBlock) {
                $p = null;
                continue;
            }
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'br' && $p === null) {
                $root->removeChild($child);
                continue;
            }
            if ($p === null) {
                if ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) === '') {
                    continue;
                }
                $p = $doc->createElement('p');
                $root->insertBefore($p, $child);
            }
            $p->appendChild($child);
        }
    }

    private static function walk(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child === null) {
                continue;
            }
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            self::walk($child);

            if ($tag === 'div') {
                // div dari contenteditable -> paragraf
                $p = $child->ownerDocument->createElement('p');
                while ($child->firstChild) {
                    $p->appendChild($child->firstChild);
                }
                $node->replaceChild($p, $child);
                continue;
            }
            if (!in_array($tag, self::ALLOWED, true)) {
                // Buka pembungkus, pertahankan isi teks
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $href = $tag === 'a' ? trim($child->getAttribute('href')) : null;
            while ($child->attributes->length) {
                $child->removeAttribute($child->attributes->item(0)->nodeName);
            }
            if ($tag === 'a') {
                if ($href !== null && preg_match('#^(https?://|mailto:|/)#i', $href) && !preg_match('#^//#', $href)) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('rel', 'noopener nofollow');
                    if (preg_match('#^https?://#i', $href)) {
                        $child->setAttribute('target', '_blank');
                    }
                }
            }
        }
    }
}
