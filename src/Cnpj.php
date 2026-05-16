<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * CNPJ validator, generator, and formatter.
 *
 * Supports both CNPJ shapes:
 *
 * - Legacy numeric — 14 digits.
 * - Alphanumeric — uppercase A-Z and 0-9 in positions 1-12,
 *   numeric verification digits in positions 13-14, per the 2026
 *   Receita Federal rules.
 *
 * Numeric CNPJs do not expire when alphanumeric ones come
 * online, so this is an accept-both library forever, not a
 * transitional one.
 *
 * @see https://www.gov.br/receitafederal/pt-br/centrais-de-conteudo/publicacoes/documentos-tecnicos/cnpj
 */
final class Cnpj
{
    private const string MASK_PATTERN = '#[\s./-]#';

    /** @var array<int, int> */
    private const array FIRST_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    /** @var array<int, int> */
    private const array SECOND_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private const string ALPHANUMERIC_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const int ALPHANUMERIC_CHARSET_LENGTH = 36;

    /**
     * Checks whether the value is a syntactically valid CNPJ.
     *
     * Accepts raw 14-character input or canonical masked form
     * (`XX.XXX.XXX/XXXX-XX`); whitespace, periods, slashes, and
     * hyphens are stripped, and remaining letters are normalized
     * to uppercase before checking. Positions 1-12 may be A-Z
     * (case-insensitive) or 0-9; positions 13-14 must be digits.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-character sequences are rejected despite passing
     * mod-11; they are the conventional placeholder values
     * across the Brazilian validation ecosystem and never
     * represent real CNPJs.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);

        if (preg_match('/^[A-Z0-9]{12}\d{2}$/', $raw) !== 1) {
            return false;
        }

        if (preg_match('/^(.)\1{13}$/', $raw) === 1) {
            return false;
        }

        if (self::calculateVerificationDigit($raw, self::FIRST_WEIGHTS) !== (int) $raw[12]) {
            return false;
        }

        return self::calculateVerificationDigit($raw, self::SECOND_WEIGHTS) === (int) $raw[13];
    }

    /**
     * Generates a valid numeric CNPJ — intended for tests and
     * seeders.
     *
     * Output matches `\d{14}`. Verification digits are computed
     * normally; the random base is regenerated when it falls
     * into an all-equal sequence so the output is never a value
     * {@see self::isValid()} would reject.
     */
    public static function generateNumeric(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 12; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{11}$/', $base) === 1);

        return self::appendVerificationDigits($base);
    }

    /**
     * Generates a valid alphanumeric CNPJ per the 2026 Receita
     * Federal rules — intended for tests and seeders.
     *
     * Output matches `[A-Z0-9]{12}\d{2}`. Verification digits are
     * computed using the ASCII-based mod-11 variant; the random
     * base is regenerated when it falls into an all-equal
     * sequence so the output is never a value
     * {@see self::isValid()} would reject.
     */
    public static function generateAlphanumeric(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 12; ++$i) {
                $base .= self::ALPHANUMERIC_CHARSET[random_int(0, self::ALPHANUMERIC_CHARSET_LENGTH - 1)];
            }
        } while (preg_match('/^(.)\1{11}$/', $base) === 1);

        return self::appendVerificationDigits($base);
    }

    /**
     * Formats a CNPJ in the canonical masked form
     * `XX.XXX.XXX/XXXX-XX`.
     *
     * Letters are normalized to uppercase before the mask is
     * applied — `'12abc34501de35'` becomes
     * `'12.ABC.345/01DE-35'`. This matches the canonical form
     * defined by Receita Federal and aligns with the
     * case-insensitive {@see self::isValid()}.
     *
     * Tolerant: when the payload does not match the alphanumeric
     * CNPJ shape (`[A-Z0-9]{12}\d{2}`) after stripping mask
     * characters and uppercasing, the input is returned
     * unchanged rather than raising. Does not validate check
     * digits — that is {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^[A-Z0-9]{12}\d{2}$/', $raw) !== 1) {
            return $value;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            mb_substr($raw, 0, 2),
            mb_substr($raw, 2, 3),
            mb_substr($raw, 5, 3),
            mb_substr($raw, 8, 4),
            mb_substr($raw, 12, 2),
        );
    }

    /**
     * Normalizes a CNPJ to its canonical raw form: strips mask
     * characters (whitespace, `.`, `/`, `-`) and uppercases
     * letters in one pass.
     *
     * Public for callers that need to canonicalize a value
     * before storage or comparison without paying for a full
     * {@see self::isValid()} pass.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', mb_strtoupper($value));
    }

    /**
     * @param array<int, int> $weights
     */
    private static function calculateVerificationDigit(string $value, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $i => $weight) {
            $sum += (ord($value[$i]) - 48) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private static function appendVerificationDigits(string $base): string
    {
        $dv1 = self::calculateVerificationDigit($base, self::FIRST_WEIGHTS);
        $partial = $base.$dv1;
        $dv2 = self::calculateVerificationDigit($partial, self::SECOND_WEIGHTS);

        return $partial.$dv2;
    }
}
