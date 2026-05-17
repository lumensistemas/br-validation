<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

use LumenSistemas\BrValidation\Concerns\Mod11;

/**
 * CNS (Cartão Nacional de Saúde) validator, generator, and
 * formatter.
 *
 * The 15-digit SUS health card number comes in two structural
 * shapes, distinguished by the first digit:
 *
 * - Definitive (first digit 1 or 2) — positions 1–11 carry the
 *   citizen's PIS/PASEP/NIT, positions 12–14 are `000` or `001`
 *   (the latter marks the appendix-bump case used when the
 *   single-digit DV would have landed on 10), and position 15 is
 *   the check digit.
 * - Provisional (first digit 7, 8, or 9) — positions 1–15 carry
 *   no internal structure beyond the first-digit type; only the
 *   full mod-11 weighted sum constrains the number.
 *
 * Both shapes share a unified check: the sum of
 * `digit[i] * (15 - i)` for `i = 0..14` must be divisible by 11.
 * The validator additionally enforces the `000`/`001` appendix
 * pattern for definitive cards so that arithmetically-conforming
 * numbers the SUS would never actually issue (e.g. a `002`
 * appendix) are rejected.
 */
final class Cns
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /** @var array<int, int> */
    private const array WEIGHTS = [15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1];

    /**
     * Checks whether the value is a syntactically valid CNS.
     *
     * Accepts raw 15-digit input or canonical masked form
     * (`XXX XXXX XXXX XXXX`); whitespace, periods, and hyphens
     * are stripped before checking.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-digit sequences are rejected for consistency
     * with the rest of the library; none naturally pass the
     * type + mod-11 checks anyway.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);

        if (preg_match('/^\d{15}$/', $raw) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{14}$/', $raw) === 1) {
            return false;
        }

        $first = $raw[0];
        if (!in_array($first, ['1', '2', '7', '8', '9'], true)) {
            return false;
        }

        if ($first === '1' || $first === '2') {
            $appendix = mb_substr($raw, 11, 3);
            if ($appendix !== '000' && $appendix !== '001') {
                return false;
            }
        }

        return Mod11::weightedSum($raw, self::WEIGHTS) % 11 === 0;
    }

    /**
     * Generates a valid definitive CNS (first digit 1 or 2) —
     * intended for tests and seeders.
     *
     * The appendix-bump case is handled internally: when the
     * preliminary DV would land on 10, the `001` appendix is
     * used and the DV is recomputed against the bumped sum.
     */
    public static function generateDefinitive(): string
    {
        do {
            $base = (string) random_int(1, 2);
            for ($i = 0; $i < 10; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{10}$/', $base) === 1);

        $sum = 0;
        for ($i = 0; $i < 11; ++$i) {
            $sum += (ord($base[$i]) - 48) * (15 - $i);
        }

        $remainder = $sum % 11;
        $dv = $remainder === 0 ? 0 : 11 - $remainder;

        if ($dv === 10) {
            $appendix = '001';
            $bumpedRemainder = ($sum + 2) % 11;
            $dv = $bumpedRemainder === 0 ? 0 : 11 - $bumpedRemainder;
        } else {
            $appendix = '000';
        }

        return $base.$appendix.$dv;
    }

    /**
     * Generates a valid provisional CNS (first digit 7, 8, or 9)
     * — intended for tests and seeders.
     *
     * The random 14-digit base is regenerated when the resulting
     * DV would land on 10 (no single digit can satisfy the
     * mod-11 constraint in that case), so the output is never a
     * value {@see self::isValid()} would reject.
     */
    public static function generateProvisional(): string
    {
        do {
            $base = (string) random_int(7, 9);
            for ($i = 0; $i < 13; ++$i) {
                $base .= (string) random_int(0, 9);
            }

            $sum = 0;
            for ($i = 0; $i < 14; ++$i) {
                $sum += (ord($base[$i]) - 48) * (15 - $i);
            }

            $remainder = $sum % 11;
            $dv = $remainder === 0 ? 0 : 11 - $remainder;
        } while ($dv === 10);

        return $base.$dv;
    }

    /**
     * Formats a CNS in the canonical masked form
     * `XXX XXXX XXXX XXXX` (3 + 4 + 4 + 4 digits, separated by
     * single spaces), matching how the number is printed on the
     * SUS card.
     *
     * Tolerant: when the payload is not 15 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate the check digit or type —
     * that is {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^\d{15}$/', $raw) !== 1) {
            return $value;
        }

        return sprintf(
            '%s %s %s %s',
            mb_substr($raw, 0, 3),
            mb_substr($raw, 3, 4),
            mb_substr($raw, 7, 4),
            mb_substr($raw, 11, 4),
        );
    }

    /**
     * Normalizes a CNS to its canonical raw form: strips mask
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
