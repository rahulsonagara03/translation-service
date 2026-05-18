<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTranslationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'translations:seed
        {--count=100000 : Number of translations to create}
        {--chunk=1000 : Insert chunk size}
        {--fresh : Delete existing translations and tags first}';

    /**
     * @var string
     */
    protected $description = 'Bulk load translations for scalability and export benchmarks.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));
        $chunkSize = max(100, (int) $this->option('chunk'));
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt'];
        $groups = ['auth', 'common', 'dashboard', 'settings', 'checkout', 'profile'];
        $tagNames = ['mobile', 'desktop', 'web', 'admin', 'checkout'];

        if ($this->option('fresh')) {
            DB::table('translation_tag')->delete();
            DB::table('translations')->delete();
            DB::table('tags')->delete();
        }

        $tagIds = collect($tagNames)
            ->mapWithKeys(fn (string $name): array => [
                $name => Tag::query()->firstOrCreate(['name' => $name])->getKey(),
            ])
            ->all();

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($offset = 0; $offset < $count; $offset += $chunkSize) {
            $limit = min($chunkSize, $count - $offset);
            $now = now();
            $records = [];
            $keys = [];

            for ($index = 0; $index < $limit; $index++) {
                $absoluteIndex = $offset + $index;
                $key = 'key_'.$absoluteIndex;
                $keys[] = $key;

                $records[] = [
                    'locale' => $locales[$absoluteIndex % count($locales)],
                    'group' => $groups[$absoluteIndex % count($groups)],
                    'key' => $key,
                    'value' => 'Translation value '.$absoluteIndex,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('translations')->insertOrIgnore($records);

            $translations = DB::table('translations')
                ->select(['id', 'key'])
                ->whereIn('key', $keys)
                ->get();

            $pivotRows = [];

            foreach ($translations as $translation) {
                $number = (int) substr($translation->key, 4);
                $pivotRows[] = [
                    'translation_id' => $translation->id,
                    'tag_id' => $tagIds[$tagNames[$number % count($tagNames)]],
                ];

                if ($number % 3 === 0) {
                    $pivotRows[] = [
                        'translation_id' => $translation->id,
                        'tag_id' => $tagIds['web'],
                    ];
                }
            }

            DB::table('translation_tag')->insertOrIgnore($pivotRows);
            $bar->advance($limit);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Seeded {$count} translations.");

        return self::SUCCESS;
    }
}