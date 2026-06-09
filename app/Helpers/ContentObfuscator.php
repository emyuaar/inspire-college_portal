<?php

namespace App\Helpers;

use DOMDocument;
use DOMText;
use DOMXPath;

class ContentObfuscator
{
    public static function obfuscate(?string $html): string
    {
        if (empty(trim((string) $html))) {
            return '';
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        $wrappedHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>' . $html . '</body>';

        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()[not(ancestor::script) and not(ancestor::style) and normalize-space(.) != ""]');

        foreach ($textNodes as $node) {
            /** @var DOMText $node */
            $text = $node->nodeValue;

            if (strlen(trim($text)) < 15) {
                continue;
            }

            $words = explode(' ', $text);
            if (count($words) < 5) {
                continue;
            }

            $fragment = $dom->createDocumentFragment();

            foreach ($words as $index => $word) {
                $fragment->appendChild($dom->createTextNode($word . ' '));

                if ($index > 0 && $index % 7 === 0) {
                    $junkSpan = $dom->createElement('span', substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5));
                    $junkSpan->setAttribute('style', 'position:absolute; opacity:0; pointer-events:none; font-size:0px; user-select:none;');
                    $junkSpan->setAttribute('aria-hidden', 'true');
                    $fragment->appendChild($junkSpan);
                }
            }

            $node->parentNode->replaceChild($fragment, $node);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $obfuscatedHtml = '';

        if ($body) {
            foreach ($body->childNodes as $child) {
                $obfuscatedHtml .= $dom->saveHTML($child);
            }
        } else {
            $obfuscatedHtml = $html;
        }

        libxml_clear_errors();

        return $obfuscatedHtml;
    }
}
