<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_streams_nested_frontend_payload_and_uses_etag(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['translations:read']);

        $web = Tag::factory()->create(['name' => 'web']);
        $translation = Translation::factory()->create([
            'locale' => 'en',
            'group' => 'auth',
            'key' => 'login.button',
            'value' => 'Log in',
        ]);
        $translation->tags()->attach($web);

        $response = $this->getJson('/api/translations/export?locale=en&tags=web');

        $response
            ->assertOk()
            ->assertHeader('ETag')
            ->assertJsonPath('meta.locale', 'en')
            ->assertJsonPath('meta.tags', ['web']);

        $this->assertSame('Log in', $response->json('data.en.auth')['login.button']);

        $this->withHeader('If-None-Match', $response->headers->get('ETag'))
            ->getJson('/api/translations/export?locale=en&tags=web')
            ->assertStatus(304);
    }

    public function test_export_performance_smoke_test(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['translations:read']);

        $web = Tag::factory()->create(['name' => 'web']);

        Translation::factory()
            ->count(1000)
            ->sequence(fn ($sequence): array => [
                'locale' => 'en',
                'group' => 'group'.($sequence->index % 10),
                'key' => 'key'.$sequence->index,
                'value' => 'Value '.$sequence->index,
            ])
            ->create()
            ->each(fn (Translation $translation) => $translation->tags()->attach($web->id));

        $startedAt = hrtime(true);

        $this->getJson('/api/translations/export?locale=en&tags=web')->assertOk();

        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        $this->assertLessThan(500, $elapsedMilliseconds);
    }
}