<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

use LumenSistemas\BrValidation\Concerns\Mod11;

/**
 * CPF validator, generator, and formatter.
 *
 * The 11-digit Brazilian individual taxpayer identifier (Cadastro
 * de Pessoas Físicas) issued by the Receita Federal. Encodes a
 * 9-digit sequential base followed by two mod-11 check digits;
 * the first check digit is computed over the base with weights
 * `10..2`, the second over the base + first DV with weights
 * `11..2`. Both DV calculations reduce remainders below 2 to 0,
 * matching the canonical Receita Federal algorithm.
 *
 * All-equal-digit sequences (`11111111111`, `22222222222`, …)
 * pass the mod-11 check yet are universally treated as
 * placeholder values across the Brazilian validation ecosystem;
 * {@see self::isValid()} rejects them to match that convention.
 *
 * @see https://www.gov.br/receitafederal/pt-br/assuntos/orientacao-tributaria/cadastros/cpf
 */
final class Cpf
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /** @var array<int, int> */
    private const array FIRST_WEIGHTS = [10, 9, 8, 7, 6, 5, 4, 3, 2];

    /** @var array<int, int> */
    private const array SECOND_WEIGHTS = [11, 10, 9, 8, 7, 6, 5, 4, 3, 2];

    /**
     * Checks whether the value is a syntactically valid CPF.
     *
     * Accepts raw 11-digit input or canonical masked form
     * (`XXX.XXX.XXX-XX`); whitespace, periods, and hyphens are
     * stripped before checking.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-digit sequences (`11111111111`, `22222222222`, …)
     * are rejected despite passing mod-11; they are the
     * conventional placeholder values across the Brazilian
     * validation ecosystem and never represent real CPFs.
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

        if (Mod11::dv($raw, self::FIRST_WEIGHTS) !== (int) $raw[9]) {
            return false;
        }

        return Mod11::dv($raw, self::SECOND_WEIGHTS) === (int) $raw[10];
    }

    /**
     * Generates a valid 11-digit CPF — intended for tests and
     * seeders.
     *
     * Verification digits are computed normally; the random base is
     * regenerated when it falls into an all-equal sequence so the
     * output is never a value {@see self::isValid()} would
     * reject.
     */
    public static function generate(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 9; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{8}$/', $base) === 1);

        $dv1 = Mod11::dv($base, self::FIRST_WEIGHTS);
        $partial = $base.$dv1;
        $dv2 = Mod11::dv($partial, self::SECOND_WEIGHTS);

        return $partial.$dv2;
    }

    /**
     * Formats a CPF in the canonical masked form
     * `XXX.XXX.XXX-XX`.
     *
     * Tolerant: when the payload is not 11 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate verification digits — that is
     * {@see self::isValid()}'s job.
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
            mb_substr($raw, 3, 3),
            mb_substr($raw, 6, 3),
            mb_substr($raw, 9, 2),
        );
    }

    /**
     * Normalizes a CPF to its canonical raw form: strips mask
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
