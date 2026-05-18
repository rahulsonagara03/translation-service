<?php

namespace App\Services\Translations;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TranslationSearch
{
    use ParsesTagFilters;

    public function __construct(private readonly TranslationTagNormalizer $tagNormalizer) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Translation>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Translation::query()
            ->select(['id', 'locale', 'group', 'key', 'value', 'created_at', 'updated_at'])
            ->with(['tags:id,name'])
            ->when(isset($filters['locale']), fn (Builder $query): Builder => $query->where('locale', $filters['locale']))
            ->when(isset($filters['group']), fn (Builder $query): Builder => $query->where('group', $filters['group']))
            ->when(isset($filters['key']), fn (Builder $query): Builder => $query->where('key', 'like', "%{$filters['key']}%"))
            ->when(isset($filters['content']), fn (Builder $query): Builder => $query->where('value', 'like', "%{$filters['content']}%"))
            ->orderBy('locale')
            ->orderBy('group')
            ->orderBy('key');

        if (isset($filters['q'])) {
            $q = $filters['q'];

            $query->where(function (Builder $query) use ($q): void {
                $query
                    ->where('key', 'like', "%{$q}%")
                    ->orWhere('value', 'like', "%{$q}%");
            });
        }

        $tags = $this->parseTagFilter($filters['tags'] ?? null);
        $tagMode = $filters['tag_mode'] ?? 'all';

        if ($tags !== []) {
            if ($tagMode === 'any') {
                $query->whereHas('tags', fn (Builder $query): Builder => $query->whereIn('name', $tags));
            } else {
                foreach ($tags as $tag) {
                    $query->whereHas('tags', fn (Builder $query): Builder => $query->where('name', $tag));
                }
            }
        }

        return $query->paginate((int) ($filters['per_page'] ?? 25));
    }
}