<?php

namespace App\Services\Translations;

use Illuminate\Support\Str;

class TranslationTagNormalizer
{
    /**
     * @param  array<int, string>  $tags
     * @return list<string>
     */
    public function normalize(array $tags): array
    {
        return collect($tags)
            ->map(fn (string $tag): string => Str::lower(trim($tag)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}