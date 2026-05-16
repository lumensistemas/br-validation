<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * NF-e access key (chave de acesso) validator, generator, and
 * formatter.
 *
 * The 44-digit access key encodes UF code, emission year/month,
 * issuer CNPJ, modelo, série, número, tipo de emissão, código
 * aleatório, and a single mod-11 check digit in the trailing
 * position. The same structural shape and check-digit algorithm
 * cover NF-e (modelo 55), NFC-e (modelo 65) and the broader SEFAZ
 * document family (CT-e, MDF-e, BP-e, …); this class validates
 * the shape and the check digit only and does not constrain
 * modelo.
 *
 * @see https://www.nfe.fazenda.gov.br
 */
final class Nfe
{
    private const string MASK_PATTERN = '#[\s.]#';

    /**
     * Mod-11 weights for positions 1..43, derived from the
     * rightmost-first 2..9 cycle of the SEFAZ algorithm.
     *
     * @var array<int, int>
     */
    private const array WEIGHTS = [
        4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9,
        8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5,
        4, 3, 2,
    ];

    /**
     * Checks whether the value is a syntactically valid NF-e
     * access key.
     *
     * Accepts raw 44-digit input or canonical masked form (11
     * groups of 4 digits separated by spaces); whitespace and
     * periods are stripped before checking.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     *
     * All-equal-digit sequences are rejected despite the fact
     * that some pass mod-11; they are conventional placeholder
     * values across the Brazilian validation ecosystem and never
     * represent real access keys.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);

        if (preg_match('/^\d{44}$/', $raw) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{43}$/', $raw) === 1) {
            return false;
        }

        return self::calculateVerificationDigit($raw) === (int) $raw[43];
    }

    /**
     * Generates a valid 44-digit NF-e access key — intended for
     * tests and seeders.
     *
     * The verification digit is computed normally; the random
     * base is regenerated when it falls into an all-equal
     * sequence so the output is never a value
     * {@see self::isValid()} would reject. No semantic structure
     * (UF, CNPJ, modelo, …) is enforced — callers that need a
     * realistic key should compose their own base and append
     * {@see self::format()} as needed.
     */
    public static function generate(): string
    {
        do {
            $base = '';
            for ($i = 0; $i < 43; ++$i) {
                $base .= (string) random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{42}$/', $base) === 1);

        return $base.self::calculateVerificationDigit($base);
    }

    /**
     * Formats an NF-e access key in the canonical masked form:
     * eleven groups of four digits separated by single spaces, as
     * printed on the DANFE.
     *
     * Tolerant: when the payload is not 44 digits after stripping
     * mask characters, the input is returned unchanged rather
     * than raising. Does not validate the check digit — that is
     * {@see self::isValid()}'s job.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);

        if (preg_match('/^\d{44}$/', $raw) !== 1) {
            return $value;
        }

        return rtrim(chunk_split($raw, 4, ' '));
    }

    /**
     * Normalizes an NF-e access key to its canonical raw form:
     * strips mask characters (whitespace and `.`), leaving digits
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
