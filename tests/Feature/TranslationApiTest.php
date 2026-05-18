<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_view_and_update_translation_with_tags(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['translations:read', 'translations:write']);

        $createResponse = $this->postJson('/api/translations', [
            'locale' => 'en',
            'group' => 'auth',
            'key' => 'login.button',
            'value' => 'Log in',
            'tags' => ['Web', 'mobile'],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.tags', ['web', 'mobile']);

        $translationId = $createResponse->json('data.id');

        $this->getJson("/api/translations/{$translationId}")
            ->assertOk()
            ->assertJsonPath('data.key', 'login.button');

        $this->patchJson("/api/translations/{$translationId}", [
            'value' => 'Sign in',
            'tags' => ['desktop'],
        ])
            ->assertOk()
            ->assertJsonPath('data.value', 'Sign in')
            ->assertJsonPath('data.tags', ['desktop']);
    }

    public function test_duplicate_locale_group_key_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['translations:read', 'translations:write']);

        Translation::factory()->create([
            'locale' => 'en',
            'group' => 'auth',
            'key' => 'login.button',
        ]);

        $this->postJson('/api/translations', [
            'locale' => 'en',
            'group' => 'auth',
            'key' => 'login.button',
            'value' => 'Log in',
        ])->assertJsonValidationErrors('key');
    }

    public function test_user_can_search_by_tag_key_and_content(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['translations:read', 'translations:write']);

        $mobile = Tag::factory()->create(['name' => 'mobile']);
        $web = Tag::factory()->create(['name' => 'web']);
        $match = Translation::factory()->create([
            'locale' => 'en',
            'group' => 'checkout',
            'key' => 'checkout.submit',
            'value' => 'Pay now',
        ]);
        $miss = Translation::factory()->create([
            'locale' => 'en',
            'group' => 'checkout',
            'key' => 'checkout.cancel',
            'value' => 'Cancel',
        ]);

        $match->tags()->attach([$mobile->id, $web->id]);
        $miss->tags()->attach($web->id);

        $this->getJson('/api/translations?tags=mobile&key=submit&content=Pay')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }
}