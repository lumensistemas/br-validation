<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

use LumenSistemas\BrValidation\Concerns\Mod11;

/**
 * PIS validator, generator, and formatter.
 *
 * The same 11-digit number is issued under four different
 * program names — PIS, PASEP, NIS (Número de Identificação
 * Social) and NIT (Número de Identificação do Trabalhador) —
 * and shares a single mod-11 check digit. This class validates
 * any of them; the name "Pis" is the most familiar public label.
 */
final class Pis
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /** @var array<int, int> */
    private const array WEIGHTS = [3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    /**
     * Checks whether the value is a syntactically valid PIS.
     *
     * Accepts raw 11-digit input or canonical masked form
     * (`XXX.XXXXX.XX-X`); whitespace, periods, and hyphens are
     * stripped before checking.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-digit sequences (`11111111111`, `22222222222`, …)
     * are rejected despite the fact that `00000000000` passes
     * mod-11; they are the conventional placeholder values
     * across the Brazilian validation ecosystem and never
     * represent real PIS numbers.
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

        return Mod11::dv($raw, self::WEIGHTS) === (int) $raw[10];
    }

    /**
     * Generates a valid 11-digit PIS — intended for tests and
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

        return $base.Mod11::dv($base, self::WEIGHTS);
    }

    /**
     * Formats a PIS in the canonical masked form
     * `XXX.XXXXX.XX-X`.
     *
     * Tolerant: when the payload is not 11 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate the verification digit —
     * that is {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^\d{11}$/', $raw) !== 1) {
            return $value;
        }

        return sprintf(
            '%s.%s.%s-%s',
            mb_substr($raw, 0, 3),
            mb_substr($raw, 3, 5),
            mb_substr($raw, 8, 2),
            mb_substr($raw, 10, 1),
        );
    }

    /**
     * Normalizes a PIS to its canonical raw form: strips mask
     * characters (whitespace, `.`, `-`), leaving digits only.
     *
     * Public for callers that need to canonicalize a value
     * before storage or comparison without paying for a full
     * {@see self::isValid()} pass.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', $value);
    }
}
