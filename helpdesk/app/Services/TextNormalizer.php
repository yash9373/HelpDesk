<?php

namespace App\Services;

class TextNormalizer
{
    public static function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $parts = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stop = [
            'the','a','an','and','or','for','to','of','in','on','with','is','are',
            'i','need','please','issue','problem','request','help','cannot','cant','can',
        ];
        $tokens = array_filter($parts, fn($p) => !in_array($p, $stop));
        $tokens = array_values(array_unique($tokens));
        return $tokens;
    }
}
