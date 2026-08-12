<?php

namespace Tests\Unit\Support;

use App\Http\Requests\Admin\Reports\GenerateReportRequest;
use App\Services\Reports\ReportGenerationService;
use App\Support\ReportType;
use Tests\TestCase;

/**
 * Phase 14 — the report type registry is the only list.
 *
 * Before this there were three, and they disagreed: GenerateReportRequest accepted
 * `playback` and `screen-uptime`, ReportsAndLogsSeeder wrote `performance` and
 * `availability`, and the generator's `match` quietly fell through to the playback
 * builder for anything unrecognised. So a seeded "Screen Availability Snapshot" was
 * stored under a type nothing could produce, and asking for it would have built a
 * playback report labelled as availability.
 *
 * These tests are the guard against that reappearing.
 */
class ReportTypeRegistryTest extends TestCase
{
    public function test_the_form_request_constant_mirrors_the_registry(): void
    {
        $this->assertSame(
            ReportType::supported(),
            GenerateReportRequest::TYPES,
            'A PHP constant cannot call the registry, so this assertion is what keeps them identical.'
        );
    }

    public function test_the_supported_list_is_exactly_the_production_contract(): void
    {
        $this->assertSame(
            ['playback', 'screen-uptime'],
            ReportType::supported()
        );
    }

    public function test_every_supported_type_has_export_headers_and_a_row_formatter(): void
    {
        foreach (ReportType::supported() as $type) {
            $headers = ReportGenerationService::headers($type);

            $this->assertNotEmpty($headers, "[{$type}] has no CSV headers.");

            $formatted = ReportGenerationService::formatRow($type, []);

            $this->assertSameSize(
                $headers,
                $formatted,
                "[{$type}] formats a different number of columns than it declares headers for."
            );
        }
    }

    public function test_the_two_report_types_have_distinct_column_sets(): void
    {
        $this->assertNotSame(
            ReportGenerationService::headers(ReportType::PLAYBACK),
            ReportGenerationService::headers(ReportType::SCREEN_UPTIME),
            'The two types must not share a column set — that was the old silent fallback.'
        );
    }

    // --------------------------------------------------------------- legacy values

    public function test_legacy_values_map_to_the_type_they_always_meant(): void
    {
        $this->assertSame(ReportType::PLAYBACK, ReportType::canonical('performance'));
        $this->assertSame(ReportType::SCREEN_UPTIME, ReportType::canonical('availability'));
    }

    public function test_legacy_values_are_presentable_but_not_generatable(): void
    {
        foreach (['performance', 'availability'] as $legacy) {
            $this->assertTrue(ReportType::isLegacy($legacy));
            $this->assertTrue(ReportType::isPresentable($legacy), "[{$legacy}] must still render.");
            $this->assertFalse(ReportType::isSupported($legacy), "[{$legacy}] must not be generatable.");
        }
    }

    public function test_legacy_values_get_the_canonical_export_columns(): void
    {
        $this->assertSame(
            ReportGenerationService::headers(ReportType::SCREEN_UPTIME),
            ReportGenerationService::headers('availability')
        );
        $this->assertSame(
            ReportGenerationService::headers(ReportType::PLAYBACK),
            ReportGenerationService::headers('performance')
        );
    }

    // -------------------------------------------------------------- unknown values

    public function test_an_unknown_value_is_returned_untouched_and_is_not_presentable(): void
    {
        $this->assertSame('some-retired-format', ReportType::canonical('some-retired-format'));
        $this->assertFalse(ReportType::isPresentable('some-retired-format'));
        $this->assertFalse(ReportType::isSupported('some-retired-format'));
        $this->assertFalse(ReportType::isLegacy('some-retired-format'));
    }

    public function test_empty_and_null_values_resolve_to_null(): void
    {
        $this->assertNull(ReportType::canonical(null));
        $this->assertNull(ReportType::canonical(''));
        $this->assertFalse(ReportType::isSupported(null));
    }

    public function test_every_supported_type_has_a_translated_label_in_both_locales(): void
    {
        foreach (['en', 'ar'] as $locale) {
            foreach (ReportType::supported() as $type) {
                $key = ReportType::labelKey($type);
                $label = __($key, [], $locale);

                $this->assertNotSame($key, $label, "[{$type}] has no {$locale} label.");
            }
        }
    }

    public function test_a_fallback_label_exists_for_values_with_no_translation(): void
    {
        $this->assertSame('Some retired format', ReportType::fallbackLabel('some-retired-format'));
        $this->assertSame('Performance', ReportType::fallbackLabel('performance'));
    }
}
