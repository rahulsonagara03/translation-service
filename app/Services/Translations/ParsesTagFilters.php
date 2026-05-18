<?php

namespace App\Services\Translations;

trait ParsesTagFilters
{
    /**
     * @return list<string>
     */
    protected function parseTagFilter(?string $tags): array
    {
        if ($tags === null || trim($tags) === '') {
            return [];
        }

        return $this->tagNormalizer->normalize(explode(',', $tags));
    }
}