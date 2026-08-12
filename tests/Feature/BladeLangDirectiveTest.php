<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class BladeLangDirectiveTest extends TestCase
{
    // Blade::render() boots the shared view composers, which read seo_metas.
    // Without a migrated schema the directive assertions never got to run.
    use RefreshDatabase;

    public function testDirectiveUsesFallbackWhenTranslationMissing(): void
    {
        app()->setLocale('en');

        $output = trim(Blade::render("@t('missing.key', 'Fallback text')"));

        $this->assertSame('Fallback text', $output);
    }

    public function testDirectiveReturnsTranslationWhenAvailable(): void
    {
        app()->setLocale('en');
        Lang::addLines(['existing.key' => 'Translated value'], 'en');

        $output = trim(Blade::render("@t('existing.key', 'Fallback text')"));

        $this->assertSame('Translated value', $output);
    }
}
