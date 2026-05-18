<?php

namespace App\Helpers;

use DOMDocument;
use DOMXPath;
use DOMText;

class ContentObfuscator
{
    /**
     * Obfuscate HTML content to deter easy copying.
     */
    public static function obfuscate(?string $html): string
    {
        if (empty(trim((string)$html))) {
            return '';
        }

        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        
        // Wrap in a stable structure to ensure valid parsing
        $wrappedHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . 
                       '<body>' . $html . '</body>';
        
        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        
        // Find all text nodes that are not empty and not inside script/style tags
        $textNodes = $xpath->query('//text()[not(ancestor::script) and not(ancestor::style) and normalize-space(.) != ""]');

        $junkWords = ['&#8203;', '&nbsp;', '<!-- protected -->', '<span style="display:none;user-select:none;">_</span>'];

        foreach ($textNodes as $node) {
            /** @var DOMText $node */
            $text = $node->nodeValue;
            
            // Skip very short text
            if (strlen(trim($text)) < 15) {
                continue;
            }

            // Chunk the text
            $words = explode(' ', $text);
            if (count($words) < 5) {
                continue;
            }

            $fragment = $dom->createDocumentFragment();
            
            foreach ($words as $index => $word) {
                // Add the word
                $fragment->appendChild($dom->createTextNode($word . ' '));
                
                // Every ~7 words, insert a junk invisible span to break copy
                if ($index > 0 && $index % 7 === 0) {
                    $junkSpan = $dom->createElement('span', substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5));
                    $junkSpan->setAttribute('style', 'position:absolute; opacity:0; pointer-events:none; font-size:0px; user-select:none;');
                    $junkSpan->setAttribute('aria-hidden', 'true');
                    $fragment->appendChild($junkSpan);
                }
            }

            $node->parentNode->replaceChild($fragment, $node);
        }

        // Get inner HTML of body
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
