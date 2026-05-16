<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * Renavam (Registro Nacional de Veículos Automotores)
 * validator, generator, and formatter.
 *
 * The 11-digit vehicle registration number uses a single mod-11
 * check digit. The algorithm is the standard one: reverse the
 * 10-digit base and apply weights `[2..9, 2, 3]`, equivalent to
 * applying `[3, 2, 9, 8, 7, 6, 5, 4, 3, 2]` to the original
 * order; the trailing `< 2 ? 0 : 11 - remainder` reduction is
 * arithmetically identical to the more verbose
 * "multiply sum by 10, mod 11, clamp 10 to 0" form found in
 * Detran documentation.
 *
 * Format note: Renavam has no canonical visual mask; it is
 * printed as 11 bare digits on the CRLV. {@see self::format()}
 * therefore returns the normalized raw form rather than
 * inventing a separator scheme.
 */
final class Renavam
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /** @var array<int, int> */
    private const array WEIGHTS = [3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    /**
     * Checks whether the value is a syntactically valid Renavam.
     *
     * Accepts raw 11-digit input or input with whitespace,
     * periods, or hyphens, which are stripped before checking.
     * Renavams shorter than 11 digits — a pre-2007 nine-digit
     * record, for example — must be left-padded with zeros by
     * the caller before validation.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-digit sequences are rejected for consistency
     * with the rest of the library.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);

        if (preg_match('/^\d{11}$/', $raw) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $raw) === 1) {
            return false;
        }

        return self::calculateVerificationDigit($raw) === (int) $raw[10];
    }

    /**
     * Generates a valid 11-digit Renavam — intended for tests and
     * seeders.
     *
     * The verification digit is computed normally; the random
     * base is regenerated when it falls into an all-equal
     * sequence so the output is never a value
     * {@see self::isValid()} would reject.
     */
    public static function generate(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 10; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{9}$/', $base) === 1);

        return $base.self::calculateVerificationDigit($base);
    }

    /**
     * Returns the canonical raw form of a Renavam: the 11 bare
     * digits with mask characters stripped.
     *
     * Renavam has no canonical visual mask, so the output of
     * this method equals {@see self::normalize()} for any
     * 11-digit input. The method exists for API symmetry with
     * the rest of the library.
     *
     * Tolerant: when the payload is not 11 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate the check digit — that is
     * {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^\d{11}$/', $raw) !== 1) {
            return $value;
        }

        return $raw;
    }

    /**
     * Normalizes a Renavam to its canonical raw form: strips
     * mask characters (whitespace, `.`, `-`), leaving digits
     * only.
     *
     * Public for callers that need to canonicalize a value
     * before storage or comparison without paying for a full
     * {@see self::isValid()} pass.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', $value);
    }

    private static function calculateVerificationDigit(string $value): int
    {
        $sum = 0;
        foreach (self::WEIGHTS as $i => $weight) {
            $sum += (ord($value[$i]) - 48) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
