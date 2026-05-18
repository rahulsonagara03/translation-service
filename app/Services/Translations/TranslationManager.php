<?php

namespace App\Services\Translations;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;

class TranslationManager
{
    public function __construct(private readonly TranslationTagNormalizer $tagNormalizer) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Translation
    {
        return DB::transaction(function () use ($attributes): Translation {
            $tags = $attributes['tags'] ?? [];
            unset($attributes['tags']);

            $translation = Translation::query()->create($attributes);
            $this->syncTags($translation, $tags);

            return $translation->load('tags');
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Translation $translation, array $attributes): Translation
    {
        return DB::transaction(function () use ($translation, $attributes): Translation {
            $shouldSyncTags = array_key_exists('tags', $attributes);
            $tags = $attributes['tags'] ?? [];
            unset($attributes['tags']);

            if ($attributes !== []) {
                $translation->fill($attributes);
                $translation->save();
            }

            if ($shouldSyncTags) {
                $this->syncTags($translation, $tags);
            }

            return $translation->refresh()->load('tags');
        });
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function syncTags(Translation $translation, array $tags): void
    {
        $tagIds = collect($this->tagNormalizer->normalize($tags))
            ->map(function (string $tag): int {
                return Tag::query()->firstOrCreate(['name' => $tag])->getKey();
            })
            ->all();

        $translation->tags()->sync($tagIds);
    }
}