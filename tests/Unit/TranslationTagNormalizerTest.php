<?php

namespace Tests\Unit;

use App\Services\Translations\TranslationTagNormalizer;
use PHPUnit\Framework\TestCase;

class TranslationTagNormalizerTest extends TestCase
{
    public function test_it_trims_lowercases_and_deduplicates_tags(): void
    {
        $normalizer = new TranslationTagNormalizer;

        $this->assertSame(
            ['web', 'mobile'],
            $normalizer->normalize([' Web ', 'web', 'MOBILE', ''])
        );
    }
}