<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * CEP (Código de Endereçamento Postal) validator, generator,
 * and formatter.
 *
 * The 8-digit Correios postal code. The first five digits
 * encode the geographic region; the last three subdivide it.
 * There is no check digit — this class validates the shape
 * only.
 *
 * Whether a given CEP actually corresponds to a real address
 * (or any issued range at all) requires a lookup against the
 * Correios database and is out of scope for this package.
 * Placeholders like `00000000` therefore pass {@see self::isValid()};
 * callers that need existence checks should integrate a
 * lookup service separately.
 */
final class Cep
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /**
     * Checks whether the value is a syntactically valid CEP.
     *
     * Accepts raw 8-digit input or canonical masked form
     * (`XXXXX-XXX`); whitespace, periods, and hyphens are
     * stripped before checking.
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

        return preg_match('/^\d{8}$/', self::normalize($value)) === 1;
    }

    /**
     * Generates a random 8-digit CEP-shaped string — intended
     * for tests and seeders.
     *
     * No check is made against the Correios database, so the
     * output is shape-valid but almost certainly not a real
     * postal code.
     */
    public static function generate(): string
    {
        $cep = '';
        for ($i = 0; $i < 8; ++$i) {
            $cep .= (string) random_int(0, 9);
        }

        return $cep;
    }

    /**
     * Formats a CEP in the canonical masked form `XXXXX-XXX`.
     *
     * Tolerant: when the payload is not 8 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^\d{8}$/', $raw) !== 1) {
            return $value;
        }

        return mb_substr($raw, 0, 5).'-'.mb_substr($raw, 5, 3);
    }

    /**
     * Normalizes a CEP to its canonical raw form: strips mask
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
