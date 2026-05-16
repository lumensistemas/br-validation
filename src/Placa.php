<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * Brazilian vehicle license plate (placa) validator, generator,
 * and formatter.
 *
 * Supports both plate shapes:
 *
 * - Old (pre-Mercosul) — three letters and four digits.
 * - Mercosul — three letters, one digit, one letter, two digits,
 *   per the 2018 Contran standard.
 *
 * Both shapes coexist on the road indefinitely (vehicles only
 * receive Mercosul plates on first registration or transfer), so
 * this is an accept-both library, not a transitional one.
 *
 * The two patterns cannot overlap: position 5 is a letter on
 * Mercosul plates and a digit on old plates, so detection is
 * unambiguous and a single {@see self::isValid()} entry point
 * accepts either.
 *
 * Placas carry no check digit; this class validates the shape
 * (and casing/normalization) only.
 */
final class Placa
{
    private const string MASK_PATTERN = '#[\s-]#';

    private const string OLD_PATTERN = '/^[A-Z]{3}\d{4}$/';

    private const string MERCOSUL_PATTERN = '/^[A-Z]{3}\d[A-Z]\d{2}$/';

    /**
     * Checks whether the value is a syntactically valid placa
     * (old or Mercosul shape).
     *
     * Accepts raw seven-character input or input with
     * whitespace and hyphens stripped, in either case. Letters
     * are uppercased before checking, so `'abc1d23'` and
     * `'ABC1D23'` validate equivalently.
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

        return preg_match(self::OLD_PATTERN, $raw) === 1
            || preg_match(self::MERCOSUL_PATTERN, $raw) === 1;
    }

    /**
     * Generates a valid old-format placa — intended for tests
     * and seeders.
     *
     * Output matches `[A-Z]{3}\d{4}`. No semantic constraints are
     * applied (reserved letter combinations, UF allocation
     * ranges, etc.); callers needing realism should compose their
     * own value.
     */
    public static function generateOld(): string
    {
        $out = '';
        for ($i = 0; $i < 3; ++$i) {
            $out .= chr(random_int(65, 90));
        }
        for ($i = 0; $i < 4; ++$i) {
            $out .= (string) random_int(0, 9);
        }

        return $out;
    }

    /**
     * Generates a valid Mercosul placa — intended for tests and
     * seeders.
     *
     * Output matches `[A-Z]{3}\d[A-Z]\d{2}`. No semantic
     * constraints are applied (reserved letter combinations, UF
     * allocation ranges, etc.); callers needing realism should
     * compose their own value.
     */
    public static function generateMercosul(): string
    {
        $out = '';
        for ($i = 0; $i < 3; ++$i) {
            $out .= chr(random_int(65, 90));
        }
        $out .= (string) random_int(0, 9);
        $out .= chr(random_int(65, 90));
        for ($i = 0; $i < 2; ++$i) {
            $out .= (string) random_int(0, 9);
        }

        return $out;
    }

    /**
     * Formats a placa in its canonical form:
     *
     * - Old: `AAA-9999` (hyphen between letters and digits).
     * - Mercosul: `AAA9A99` (no separator, matching the
     *   physical plate).
     *
     * Letters are uppercased before the mask is applied —
     * `'abc1234'` becomes `'ABC-1234'`, `'abc1d23'` becomes
     * `'ABC1D23'`.
     *
     * Tolerant: when the payload does not match either placa
     * shape after stripping mask characters and uppercasing,
     * the input is returned unchanged rather than raising.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match(self::OLD_PATTERN, $raw) === 1) {
            return mb_substr($raw, 0, 3).'-'.mb_substr($raw, 3, 4);
        }

        if (preg_match(self::MERCOSUL_PATTERN, $raw) === 1) {
            return $raw;
        }

        return $value;
    }

    /**
     * Normalizes a placa to its canonical raw form: strips
     * whitespace and hyphens and uppercases letters in one
     * pass.
     *
     * Public for callers that need to canonicalize a value
     * before storage or comparison without paying for a full
     * {@see self::isValid()} pass.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', mb_strtoupper($value));
    }
}
