<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * Boleto bancário validator, generator, and formatter.
 *
 * Supports both representations of the same instrument:
 *
 * - Linha digitável — 47 digits, the human-readable form
 *   printed on the slip. Carries three mod-10 (Luhn-style)
 *   field check digits and one mod-11 general check digit
 *   inherited from the barcode.
 * - Código de barras — 44 digits, the machine-readable form
 *   beneath the barcode. Carries the single mod-11 general
 *   check digit in position 5.
 *
 * The two forms encode the same payload (bank code, currency,
 * factor de vencimento, valor, and bank-specific free field);
 * {@see self::isValid()} accepts either and dispatches by
 * length. The two algorithms are kept consistent: a linha
 * digitável is valid iff all three mod-10 field DVs match
 * AND the barcode it reconstructs to passes the mod-11 check.
 *
 * This class targets boletos bancários (banking slips, the
 * `XXXXX.XXXXX XXXXX.XXXXXX XXXXX.XXXXXX X XXXXXXXXXXXXXX`
 * layout). Concessionárias (utility slips) have a different
 * 48-digit shape and are out of scope.
 */
final class Boleto
{
    private const string MASK_PATTERN = '#[\s.-]#';

    /**
     * Mod-11 weights for the 43 barcode positions excluding the
     * general DV (position 5). Same shape as
     * {@see Nfe::WEIGHTS} — both come from the SEFAZ/Febraban
     * "rightmost gets weight 2, cycle 2..9" rule.
     *
     * @var array<int, int>
     */
    private const array MOD11_WEIGHTS = [
        4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9,
        8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5,
        4, 3, 2,
    ];

    /**
     * Checks whether the value is a valid boleto bancário in
     * either linha digitável (47 digits) or barcode (44 digits)
     * form.
     *
     * Accepts mask characters (whitespace, periods, hyphens),
     * which are stripped before checking. Accepts any input
     * type — non-string values return false rather than
     * raising.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $raw = self::normalize($value);
        $len = mb_strlen($raw);

        if ($len === 47) {
            return self::isValidLinhaDigitavel($raw);
        }

        if ($len === 44) {
            return self::isValidBarcode($raw);
        }

        return false;
    }

    /**
     * Generates a valid 47-digit linha digitável with random
     * bank code, factor, valor, and free field. The currency
     * code is fixed at `9` (Real, the only value Febraban
     * currently assigns).
     */
    public static function generate(): string
    {
        $bank = self::randomDigits(3);
        $currency = '9';
        $factor = self::randomDigits(4);
        $value = self::randomDigits(10);
        $freeField = self::randomDigits(25);

        $withoutDv = $bank.$currency.$factor.$value.$freeField;
        $generalDv = self::mod11($withoutDv);

        $barcode = $bank.$currency.((string) $generalDv).$factor.$value.$freeField;

        return self::barcodeToLinhaDigitavel($barcode);
    }

    /**
     * Formats a linha digitável in the canonical masked form
     * `XXXXX.XXXXX XXXXX.XXXXXX XXXXX.XXXXXX X XXXXXXXXXXXXXX`.
     *
     * For 44-digit barcode input, returns the normalized raw
     * form (barcodes have no canonical visual mask). Tolerant:
     * inputs that match neither length are returned unchanged.
     */
    public static function format(string $value): string
    {
        $raw = self::normalize($value);
        $len = mb_strlen($raw);

        if ($len === 47) {
            return sprintf(
                '%s.%s %s.%s %s.%s %s %s',
                mb_substr($raw, 0, 5),
                mb_substr($raw, 5, 5),
                mb_substr($raw, 10, 5),
                mb_substr($raw, 15, 6),
                mb_substr($raw, 21, 5),
                mb_substr($raw, 26, 6),
                mb_substr($raw, 32, 1),
                mb_substr($raw, 33, 14),
            );
        }

        if ($len === 44) {
            return $raw;
        }

        return $value;
    }

    /**
     * Normalizes a boleto to its canonical raw form: strips
     * mask characters (whitespace, `.`, `-`), leaving digits
     * only.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace(self::MASK_PATTERN, '', $value);
    }

    private static function isValidLinhaDigitavel(string $raw): bool
    {
        if (preg_match('/^\d{47}$/', $raw) !== 1) {
            return false;
        }

        if (self::mod10(mb_substr($raw, 0, 9)) !== (int) $raw[9]) {
            return false;
        }

        if (self::mod10(mb_substr($raw, 10, 10)) !== (int) $raw[20]) {
            return false;
        }

        if (self::mod10(mb_substr($raw, 21, 10)) !== (int) $raw[31]) {
            return false;
        }

        return self::isValidBarcode(self::linhaDigitavelToBarcode($raw));
    }

    private static function isValidBarcode(string $raw): bool
    {
        if (preg_match('/^\d{44}$/', $raw) !== 1) {
            return false;
        }

        $withoutDv = mb_substr($raw, 0, 4).mb_substr($raw, 5, 39);

        return self::mod11($withoutDv) === (int) $raw[4];
    }

    private static function linhaDigitavelToBarcode(string $linha): string
    {
        return mb_substr($linha, 0, 4)
            .mb_substr($linha, 32, 1)
            .mb_substr($linha, 33, 14)
            .mb_substr($linha, 4, 5)
            .mb_substr($linha, 10, 10)
            .mb_substr($linha, 21, 10);
    }

    private static function barcodeToLinhaDigitavel(string $barcode): string
    {
        $f1Source = mb_substr($barcode, 0, 4).mb_substr($barcode, 19, 5);
        $f2Source = mb_substr($barcode, 24, 10);
        $f3Source = mb_substr($barcode, 34, 10);

        return $f1Source.((string) self::mod10($f1Source))
            .$f2Source.((string) self::mod10($f2Source))
            .$f3Source.((string) self::mod10($f3Source))
            .$barcode[4]
            .mb_substr($barcode, 5, 14);
    }

    private static function mod10(string $digits): int
    {
        $sum = 0;
        $weight = 2;
        for ($i = mb_strlen($digits) - 1; $i >= 0; --$i) {
            $product = (ord($digits[$i]) - 48) * $weight;
            if ($product > 9) {
                $product -= 9;
            }
            $sum += $product;
            $weight = $weight === 2 ? 1 : 2;
        }

        $remainder = $sum % 10;

        return $remainder === 0 ? 0 : 10 - $remainder;
    }

    private static function mod11(string $digits43): int
    {
        $sum = 0;
        foreach (self::MOD11_WEIGHTS as $i => $weight) {
            $sum += (ord($digits43[$i]) - 48) * $weight;
        }

        $dv = 11 - ($sum % 11);

        return $dv >= 10 ? 1 : $dv;
    }

    private static function randomDigits(int $count): string
    {
        $out = '';
        for ($i = 0; $i < $count; ++$i) {
            $out .= (string) random_int(0, 9);
        }

        return $out;
    }
}
