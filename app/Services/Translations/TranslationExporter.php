<?php

namespace App\Services\Translations;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationExporter
{
    use ParsesTagFilters;

    public function __construct(private readonly TranslationTagNormalizer $tagNormalizer) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function response(Request $request, array $filters): Response
    {
        $tags = $this->parseTagFilter($filters['tags'] ?? null);
        $locale = $filters['locale'] ?? null;
        $version = $this->version($locale, $tags);
        $etag = '"'.sha1(json_encode([$locale, $tags, $version], JSON_THROW_ON_ERROR)).'"';

        $headers = [
            'Cache-Control' => 'public, max-age=0, must-revalidate',
            'Content-Type' => 'application/json',
            'ETag' => $etag,
            'Surrogate-Key' => 'translations'.($locale ? " locale-{$locale}" : ''),
        ];

        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        return new StreamedResponse(
            fn () => $this->stream($locale, $tags, $version),
            200,
            $headers
        );
    }

    /**
     * @param  list<string>  $tags
     * @return array{count: int, updated_at: string|null}
     */
    public function version(?string $locale = null, array $tags = []): array
    {
        $row = $this->filteredQuery($locale, $tags)
            ->selectRaw('COUNT(*) as aggregate_count, MAX(translations.updated_at) as aggregate_updated_at')
            ->first();

        return [
            'count' => (int) ($row->aggregate_count ?? 0),
            'updated_at' => $row->aggregate_updated_at,
        ];
    }

    /**
     * @param  list<string>  $tags
     * @param  array{count: int, updated_at: string|null}  $version
     */
    private function stream(?string $locale, array $tags, array $version): void
    {
        echo '{"meta":';
        echo $this->json([
            'locale' => $locale,
            'tags' => $tags,
            'count' => $version['count'],
            'updated_at' => $version['updated_at'],
        ]);
        echo ',"data":{';

        $currentLocale = null;
        $currentGroup = null;
        $hasLocale = false;
        $hasGroup = false;
        $firstLocale = true;
        $firstGroup = true;
        $firstKey = true;

        foreach ($this->exportQuery($locale, $tags)->cursor() as $row) {
            if ($row->locale !== $currentLocale) {
                if ($hasGroup) {
                    echo '}';
                    $hasGroup = false;
                }

                if ($hasLocale) {
                    echo '}';
                }

                echo $firstLocale ? '' : ',';
                echo $this->json($row->locale).':{';

                $currentLocale = $row->locale;
                $currentGroup = null;
                $hasLocale = true;
                $firstLocale = false;
                $firstGroup = true;
            }

            if ($row->group !== $currentGroup) {
                if ($hasGroup) {
                    echo '}';
                }

                echo $firstGroup ? '' : ',';
                echo $this->json($row->group).':{';

                $currentGroup = $row->group;
                $hasGroup = true;
                $firstGroup = false;
                $firstKey = true;
            }

            echo $firstKey ? '' : ',';
            echo $this->json($row->key).':'.$this->json($row->value);
            $firstKey = false;
        }

        if ($hasGroup) {
            echo '}';
        }

        if ($hasLocale) {
            echo '}';
        }

        echo '}}';
    }

    /**
     * @param  list<string>  $tags
     */
    private function exportQuery(?string $locale, array $tags): Builder
    {
        return $this->filteredQuery($locale, $tags)
            ->select(['translations.locale', 'translations.group', 'translations.key', 'translations.value'])
            ->orderBy('translations.locale')
            ->orderBy('translations.group')
            ->orderBy('translations.key');
    }

    /**
     * @param  list<string>  $tags
     */
    private function filteredQuery(?string $locale, array $tags): Builder
    {
        $query = DB::table('translations')
            ->when($locale, fn (Builder $query): Builder => $query->where('translations.locale', $locale));

        foreach ($tags as $tag) {
            $query->whereExists(function (Builder $query) use ($tag): void {
                $query
                    ->selectRaw('1')
                    ->from('translation_tag')
                    ->join('tags', 'tags.id', '=', 'translation_tag.tag_id')
                    ->whereColumn('translation_tag.translation_id', 'translations.id')
                    ->where('tags.name', $tag);
            });
        }

        return $query;
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}