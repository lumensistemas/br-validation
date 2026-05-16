<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * Brazilian telephone number validator, generator, and
 * formatter.
 *
 * Accepts both shapes in current use:
 *
 * - Landline — 10 digits total (2-digit DDD + 8-digit number).
 * - Mobile — 11 digits total (2-digit DDD + 9-digit number),
 *   where the 9-digit subscriber number must start with `9`
 *   per the ANATEL mobile-9 mandate (in force nationwide since
 *   2017).
 *
 * The Brazilian country code (`+55`) is accepted when present;
 * {@see self::normalize()} strips it. The DDD must be in the
 * `11..99` range with neither digit being `0`; further DDD
 * semantic validation (which DDDs are actually allocated by
 * ANATEL) is intentionally out of scope, since the allocation
 * list shifts over time and is better handled by a separate
 * lookup table.
 */
final class Phone
{
    private const string MASK_PATTERN = '#[^\d+]#';

    private const string DDD_PATTERN = '/^[1-9][1-9]$/';

    /**
     * Checks whether the value is a syntactically valid
     * Brazilian phone number.
     *
     * Accepts raw digits, masked forms (`(11) 98765-4321`,
     * `(11) 3333-4444`), and E.164 (`+5511987654321`). Any
     * non-digit, non-`+` character is stripped before checking.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);
        $len = mb_strlen($raw);

        if ($len !== 10 && $len !== 11) {
            return false;
        }

        $ddd = mb_substr($raw, 0, 2);
        if (preg_match(self::DDD_PATTERN, $ddd) !== 1) {
            return false;
        }

        $number = mb_substr($raw, 2);

        if ($len === 11) {
            return $number[0] === '9';
        }

        return $number[0] !== '9';
    }

    /**
     * Generates a valid 11-digit Brazilian mobile number —
     * intended for tests and seeders.
     *
     * DDD is sampled uniformly from `11..99` with no `0` in
     * either position; the 9-digit subscriber number starts
     * with `9` per the mobile-9 mandate.
     */
    public static function generateMobile(): string
    {
        $number = '9';
        for ($i = 0; $i < 8; ++$i) {
            $number .= (string) random_int(0, 9);
        }

        return self::randomDdd().$number;
    }

    /**
     * Generates a valid 10-digit Brazilian landline number —
     * intended for tests and seeders.
     *
     * DDD is sampled uniformly from `11..99` with no `0` in
     * either position; the 8-digit subscriber number starts
     * with `2`–`5`, matching the most common landline ranges
     * in current allocation.
     */
    public static function generateLandline(): string
    {
        $number = (string) random_int(2, 5);
        for ($i = 0; $i < 7; ++$i) {
            $number .= (string) random_int(0, 9);
        }

        return self::randomDdd().$number;
    }

    /**
     * Formats a phone number in the canonical Brazilian local
     * masked form: `(DD) XXXXX-XXXX` for mobiles and
     * `(DD) XXXX-XXXX` for landlines.
     *
     * Tolerant: when the payload does not normalize to 10 or 11
     * digits, the input is returned unchanged rather than
     * raising. Does not enforce the DDD validity or mobile-9
     * rule — that is {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);
        $len = mb_strlen($raw);

        if ($len !== 10 && $len !== 11) {
            return $value;
        }

        $ddd = mb_substr($raw, 0, 2);
        $number = mb_substr($raw, 2);

        if ($len === 11) {
            return sprintf('(%s) %s-%s', $ddd, mb_substr($number, 0, 5), mb_substr($number, 5));
        }

        return sprintf('(%s) %s-%s', $ddd, mb_substr($number, 0, 4), mb_substr($number, 4));
    }

    /**
     * Formats a phone number in E.164 form (`+5511987654321`),
     * suitable for chave PIX phone keys and international
     * interop.
     *
     * Tolerant: when the payload does not normalize to 10 or 11
     * digits, the input is returned unchanged rather than
     * raising.
     */
    public static function formatE164(string $value): string
    {
        $raw = self::normalize($value);
        $len = mb_strlen($raw);

        if ($len !== 10 && $len !== 11) {
            return $value;
        }

        return '+55'.$raw;
    }

    /**
     * Normalizes a phone number to its canonical 10- or
     * 11-digit Brazilian local form: strips every non-digit
     * character, then removes a leading `+55` country code if
     * present.
     *
     * Only the `+55` prefix is stripped; bare `55` without a
     * leading `+` is left alone, because `55` is also a valid
     * Brazilian DDD (Rio Grande do Sul) and stripping it
     * unconditionally would corrupt real numbers.
     */
    public static function normalize(string $value): string
    {
        $stripped = (string) preg_replace(self::MASK_PATTERN, '', $value);

        if (str_starts_with($stripped, '+55')) {
            return mb_substr($stripped, 3);
        }

        if (str_starts_with($stripped, '+')) {
            return mb_substr($stripped, 1);
        }

        return $stripped;
    }

    private static function randomDdd(): string
    {
        return random_int(1, 9).random_int(1, 9);
    }
}
