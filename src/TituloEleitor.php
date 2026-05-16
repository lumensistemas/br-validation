<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * Título de Eleitor validator, generator, and formatter.
 *
 * The 12-digit voter registration number issued by the TSE
 * (Tribunal Superior Eleitoral) encodes a sequential number in
 * positions 1–8, a UF code in positions 9–10, and two mod-11
 * check digits in positions 11–12. The UF code is the TSE's own
 * numbering (`01..28`), distinct from the IBGE UF code; codes
 * outside that range are rejected.
 *
 * São Paulo (`01`) and Minas Gerais (`02`) follow a special
 * rule: whenever a check-digit calculation yields remainder 0,
 * the digit is bumped to 1. This is a TSE quirk preserved from
 * the original numbering schemes of those two electoral zones.
 */
final class TituloEleitor
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /** @var array<int, int> */
    private const array FIRST_WEIGHTS = [2, 3, 4, 5, 6, 7, 8, 9];

    private const int MIN_UF = 1;

    private const int MAX_UF = 28;

    private const string UF_SP = '01';

    private const string UF_MG = '02';

    /**
     * Checks whether the value is a syntactically valid Título de
     * Eleitor.
     *
     * Accepts raw 12-digit input or canonical masked form (three
     * groups of four digits separated by spaces); whitespace,
     * periods, and hyphens are stripped before checking.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-digit sequences are rejected for consistency
     * with the rest of the library; none naturally pass the UF
     * + DV checks anyway.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);

        if (preg_match('/^\d{12}$/', $raw) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{11}$/', $raw) === 1) {
            return false;
        }

        $uf = mb_substr($raw, 8, 2);
        $ufInt = (int) $uf;
        if ($ufInt < self::MIN_UF || $ufInt > self::MAX_UF) {
            return false;
        }

        if (self::calculateFirstVerificationDigit($raw, $uf) !== (int) $raw[10]) {
            return false;
        }

        return self::calculateSecondVerificationDigit($raw, $uf) === (int) $raw[11];
    }

    /**
     * Generates a valid 12-digit Título de Eleitor — intended for
     * tests and seeders.
     *
     * The UF code is sampled uniformly from `01..28`; verification
     * digits are computed normally (with the SP/MG remainder-0
     * bump applied where relevant). The random sequential base is
     * regenerated when it falls into an all-equal sequence so the
     * output is never a value {@see self::isValid()} would reject.
     */
    public static function generate(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 8; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{7}$/', $base) === 1);

        $uf = mb_str_pad((string) random_int(self::MIN_UF, self::MAX_UF), 2, '0', STR_PAD_LEFT);
        $combined = $base.$uf;

        $dv1 = self::calculateFirstVerificationDigit($combined, $uf);
        $partial = $combined.$dv1;
        $dv2 = self::calculateSecondVerificationDigit($partial, $uf);

        return $partial.$dv2;
    }

    /**
     * Formats a Título de Eleitor in the canonical masked form:
     * three groups of four digits separated by single spaces.
     *
     * Tolerant: when the payload is not 12 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate check digits or UF — that
     * is {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^\d{12}$/', $raw) !== 1) {
            return $value;
        }

        return rtrim(chunk_split($raw, 4, ' '));
    }

    /**
     * Normalizes a Título de Eleitor to its canonical raw form:
     * strips mask characters (whitespace, `.`, `-`), leaving
     * digits only.
     *
     * Public for callers that need to canonicalize a value
     * before storage or comparison without paying for a full
     * {@see self::isValid()} pass.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', $value);
    }

    private static function calculateFirstVerificationDigit(string $value, string $uf): int
    {
        $sum = 0;
        foreach (self::FIRST_WEIGHTS as $i => $weight) {
            $sum += (ord($value[$i]) - 48) * $weight;
        }

        return self::reduceModEleven($sum, $uf);
    }

    private static function calculateSecondVerificationDigit(string $value, string $uf): int
    {
        $sum = (ord($value[8]) - 48) * 7
            + (ord($value[9]) - 48) * 8
            + (ord($value[10]) - 48) * 9;

        return self::reduceModEleven($sum, $uf);
    }

    private static function reduceModEleven(int $sum, string $uf): int
    {
        $remainder = $sum % 11;

        if ($remainder === 10) {
            return 0;
        }

        if ($remainder === 0 && ($uf === self::UF_SP || $uf === self::UF_MG)) {
            return 1;
        }

        return $remainder;
    }
}
