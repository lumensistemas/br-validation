<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * CNH (Carteira Nacional de Habilitação) validator, generator,
 * and formatter.
 *
 * The 11-digit driver's license number (número de registro) uses
 * two mod-11 check digits with a quirk unique to CNH: when the
 * first digit's raw calculation lands on 10 (clamped to 0), an
 * offset of 2 is subtracted from the second digit's calculation,
 * with mod-11 wrap-around if the result goes negative. This
 * `dsc` rule does not appear in CPF, CNPJ, or PIS.
 *
 * Format note: the CNH número de registro has no canonical
 * visual mask; it is printed as bare 11 digits on the document.
 * {@see self::format()} therefore returns the normalized raw
 * form rather than inventing a separator scheme.
 */
final class Cnh
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /**
     * Checks whether the value is a syntactically valid CNH
     * número de registro.
     *
     * Accepts raw 11-digit input or input with whitespace,
     * periods, or hyphens, which are stripped before checking.
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

        [$dv1, $dsc] = self::calculateFirstVerificationDigit($raw);
        if ($dv1 !== (int) $raw[9]) {
            return false;
        }

        return self::calculateSecondVerificationDigit($raw, $dsc) === (int) $raw[10];
    }

    /**
     * Generates a valid 11-digit CNH número de registro —
     * intended for tests and seeders.
     *
     * Verification digits are computed normally (including the
     * dsc offset when applicable); the random base is regenerated
     * when it falls into an all-equal sequence so the output is
     * never a value {@see self::isValid()} would reject.
     */
    public static function generate(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 9; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{8}$/', $base) === 1);

        [$dv1, $dsc] = self::calculateFirstVerificationDigit($base);
        $partial = $base.$dv1;
        $dv2 = self::calculateSecondVerificationDigit($partial, $dsc);

        return $partial.$dv2;
    }

    /**
     * Returns the canonical raw form of a CNH número de registro:
     * the 11 bare digits with mask characters stripped.
     *
     * CNH has no canonical visual mask, so the output of this
     * method equals {@see self::normalize()} for any 11-digit
     * input. The method exists for API symmetry with the rest of
     * the library; callers may use it as the documented
     * "displayable form" entry point.
     *
     * Tolerant: when the payload is not 11 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate check digits — that is
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
     * Normalizes a CNH número de registro to its canonical raw
     * form: strips mask characters (whitespace, `.`, `-`),
     * leaving digits only.
     *
     * Public for callers that need to canonicalize a value
     * before storage or comparison without paying for a full
     * {@see self::isValid()} pass.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', $value);
    }

    /**
     * @return array{int, int} `[dv1, dsc]` — `dsc` is 2 when the
     *                         raw mod-11 calculation hits 10 (and
     *                         the digit is clamped to 0), else 0
     */
    private static function calculateFirstVerificationDigit(string $value): array
    {
        $sum = 0;
        for ($i = 0, $weight = 9; $i < 9; ++$i, --$weight) {
            $sum += (ord($value[$i]) - 48) * $weight;
        }

        $dv1 = $sum % 11;
        if ($dv1 >= 10) {
            return [0, 2];
        }

        return [$dv1, 0];
    }

    private static function calculateSecondVerificationDigit(string $value, int $dsc): int
    {
        $sum = 0;
        for ($i = 0, $weight = 1; $i < 9; ++$i, ++$weight) {
            $sum += (ord($value[$i]) - 48) * $weight;
        }

        $remainder = $sum % 11;
        if ($remainder >= 10) {
            return 0;
        }

        $dv2 = $remainder - $dsc;
        if ($dv2 < 0) {
            $dv2 += 11;
        }
        if ($dv2 >= 10) {
            return 0;
        }

        return $dv2;
    }
}
