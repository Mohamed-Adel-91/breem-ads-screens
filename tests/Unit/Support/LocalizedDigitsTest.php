<?php

namespace Tests\Unit\Support;

use App\Support\LocalizedDigits;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The digit formatter.
 *
 * Arabic presentation reads Arabic-Indic numerals; English reads ASCII. The interesting
 * cases are not the happy path — they are the things this must NOT do: reorder a sign,
 * invent a decimal separator, mangle a screen code, or convert something a computer is
 * going to read back.
 */
class LocalizedDigitsTest extends TestCase
{
    public static function arabicProvider(): array
    {
        return [
            'zero' => [0, '٠'],
            'single digit' => [5, '٥'],
            'two digits' => [20, '٢٠'],
            'three digits' => [215, '٢١٥'],
            'counter 347' => [347, '٣٤٧'],
            'counter 658' => [658, '٦٥٨'],
            'year' => [2026, '٢٠٢٦'],
            'every digit' => [1234567890, '١٢٣٤٥٦٧٨٩٠'],
            'numeric string' => ['42', '٤٢'],
        ];
    }

    #[DataProvider('arabicProvider')]
    public function test_arabic_renders_arabic_indic_digits(mixed $input, string $expected): void
    {
        $this->assertSame($expected, LocalizedDigits::format($input, 'ar'));
    }

    #[DataProvider('arabicProvider')]
    public function test_english_is_left_alone(mixed $input, string $expected): void
    {
        $this->assertSame((string) $input, LocalizedDigits::format($input, 'en'));
    }

    public function test_the_plus_sign_keeps_its_place(): void
    {
        // The public statistics read "+658". Only the digits change; the sign stays where
        // it is so the browser's bidi algorithm can place it for the paragraph direction.
        // Reordering logical text here would produce a string nobody can copy.
        $this->assertSame('+٦٥٨', LocalizedDigits::format('+658', 'ar'));
        $this->assertSame('+658', LocalizedDigits::format('+658', 'en'));
    }

    public static function nonDigitProvider(): array
    {
        return [
            'percentage' => ['99.5%', '٩٩.٥%'],
            'thousands separator' => ['1,024', '١,٠٢٤'],
            'date' => ['2026-08-13', '٢٠٢٦-٠٨-١٣'],
            'time' => ['14:30', '١٤:٣٠'],
            'range' => ['1–12', '١–١٢'],
            'negative' => ['-7', '-٧'],
            'mixed with words' => ['20 seconds', '٢٠ seconds'],
        ];
    }

    #[DataProvider('nonDigitProvider')]
    public function test_only_digits_are_substituted(string $input, string $expected): void
    {
        // Separators, signs and letters pass through untouched. Arabic typography has its
        // own decimal and thousands marks, but choosing them is a product decision nobody
        // has made, and guessing would silently change what every decimal means.
        $this->assertSame($expected, LocalizedDigits::format($input, 'ar'));
    }

    public static function machineValueProvider(): array
    {
        return [
            'screen code' => ['SCR-001'],
            'device uid' => ['device-a1b2c3d4'],
            'uuid' => ['9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'],
            'query string' => ['?page=2'],
            'url' => ['https://breem.example/en/admin-panel/ads/17'],
            'tel link' => ['tel:01112345678'],
            'media path' => ['upload/ads/creative-2026.mp4'],
            'css class' => ['col-md-6'],
        ];
    }

    /**
     * The formatter has no opinion about machine values — it is a pure digit substitution,
     * so pointing it at one WOULD corrupt it. These cases exist to document that plainly
     * and to make the boundary visible: the protection is that call sites never wrap
     * these, which the feature tests assert against real rendered pages.
     */
    #[DataProvider('machineValueProvider')]
    public function test_a_machine_value_would_be_corrupted_so_call_sites_must_not_wrap_one(string $machine): void
    {
        $formatted = LocalizedDigits::format($machine, 'ar');

        if (preg_match('/\d/', $machine)) {
            $this->assertNotSame(
                $machine,
                $formatted,
                "[{$machine}] contains digits, so it must never be passed through the formatter."
            );
        }

        // And the reverse map restores it exactly, which is what makes the substitution
        // provably lossless rather than merely plausible.
        $this->assertSame($machine, LocalizedDigits::toAscii($formatted));
    }

    public function test_round_tripping_is_lossless(): void
    {
        foreach (['0', '658', '1234567890', '+347', '99.5%', '2026-08-13'] as $value) {
            $this->assertSame($value, LocalizedDigits::toAscii(LocalizedDigits::format($value, 'ar')));
        }
    }

    public function test_the_locale_defaults_to_the_application_locale(): void
    {
        // Part of the contract: there is one locale source, App::getLocale(), set by
        // SetLocaleFromRequest. No session key, no URL parsing, no second constant.
        App::setLocale('ar');
        $this->assertSame('٦٥٨', LocalizedDigits::format(658));
        $this->assertTrue(LocalizedDigits::usesArabicIndic());

        App::setLocale('en');
        $this->assertSame('658', LocalizedDigits::format(658));
        $this->assertFalse(LocalizedDigits::usesArabicIndic());
    }

    public function test_an_unknown_locale_falls_back_to_western_digits(): void
    {
        // Only Arabic differs. Anything else must not be guessed at.
        foreach (['fr', 'de', 'ar-SA', ''] as $locale) {
            $this->assertSame('658', LocalizedDigits::format(658, $locale));
        }
    }

    public static function emptyValueProvider(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [[1, 2]],
            'object' => [new \stdClass()],
        ];
    }

    #[DataProvider('emptyValueProvider')]
    public function test_a_non_renderable_value_becomes_an_empty_string(mixed $value): void
    {
        // Views hand over whatever a model or paginator gave them. A null count must
        // render as nothing, not as the word "null" or a PHP notice.
        $this->assertSame('', LocalizedDigits::format($value, 'ar'));
        $this->assertSame('', LocalizedDigits::format($value, 'en'));
    }

    public function test_the_blade_helper_delegates_to_the_formatter(): void
    {
        // Views call localized_digits(); it must be the same behaviour, not a second map.
        App::setLocale('ar');

        $this->assertSame(LocalizedDigits::format(658), localized_digits(658));
        $this->assertSame('٦٥٨', localized_digits(658));
        $this->assertSame('658', localized_digits(658, 'en'));
    }

    public function test_zero_is_not_treated_as_empty(): void
    {
        // A count of zero is a real, displayable value. A truthiness check somewhere in
        // the chain would silently blank it.
        $this->assertSame('٠', LocalizedDigits::format(0, 'ar'));
        $this->assertSame('0', LocalizedDigits::format(0, 'en'));
        $this->assertSame('٠', localized_digits(0, 'ar'));
    }
}
