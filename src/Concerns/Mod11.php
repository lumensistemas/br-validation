<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation\Concerns;

/**
 * Shared mod-11 primitives for the validators in this package.
 *
 * Five validators (Cpf, Cnpj, Pis, Renavam, Nfe) share the
 * canonical mod-11 shape: multiply each digit by a fixed
 * weight, sum the products, and reduce via the
 * `remainder < 2 ? 0 : 11 - remainder` rule. Three more
 * (TituloEleitor, Cnh, Boleto) share the weighted-sum step but
 * apply custom reductions (SP/MG bump, dsc offset, Febraban
 * clamp); they still benefit from {@see self::weightedSum()}.
 */
final class Mod11
{
    /**
     * Sum of `digit[i] * weights[i]` for `i` in
     * `0..count(weights) - 1`.
     *
     * Iterates `$weights`, so `$digits` only needs to be at
     * least as long. The digit-from-char conversion uses
     * `ord` minus `48` (ASCII `'0'`), matching the assumption
     * every validator in this package makes after `normalize()`
     * has stripped mask characters.
     *
     * @param array<int, int> $weights
     */
    public static function weightedSum(string $digits, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $i => $weight) {
            $sum += (ord($digits[$i]) - 48) * $weight;
        }

        return $sum;
    }

    /**
     * Standard mod-11 DV reduction: returns 0 when the
     * remainder is 0 or 1, else `11 - remainder`.
     *
     * Shared by Cpf, Cnpj, Pis, Renavam, and Nfe. Validators
     * with non-standard reductions (TituloEleitor's SP/MG
     * bump, Cnh's dsc offset, Boleto's Febraban clamp)
     * implement them inline.
     */
    public static function dvFromSum(int $sum): int
    {
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    /**
     * Convenience for the common path: weighted sum followed
     * by the standard DV reduction.
     *
     * @param array<int, int> $weights
     */
    public static function dv(string $digits, array $weights): int
    {
        return self::dvFromSum(self::weightedSum($digits, $weights));
    }
}
