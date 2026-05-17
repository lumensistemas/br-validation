<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Pis;

covers(Pis::class);

describe('Pis::isValid', function (): void {
    it('accepts valid PIS numbers', function (mixed $value): void {
        expect(Pis::isValid($value))->toBeTrue();
    })->with([
        'raw' => ['12065328705'],
        'masked' => ['120.65328.70-5'],
        'masked with whitespace' => [' 120.65328.70-5 '],
        'second raw' => ['12089657202'],
        'DV of zero' => ['12056412880'],
    ]);

    it('rejects invalid PIS numbers', function (mixed $value): void {
        expect(Pis::isValid($value))->toBeFalse();
    })->with([
        'wrong check digit' => ['12065328704'],
        'flipped interior digit' => ['12065328795'],
        'too short' => ['1206532870'],
        'too long' => ['120653287050'],
        'letters' => ['abcdefghijk'],
        'non-string integer' => [12065328705],
        'non-string array' => [['value']],
        'empty string' => [''],
        'masked all equal' => ['000.00000.00-0'],
    ]);

    it('rejects all-equal-digit sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(Pis::isValid(str_repeat((string) $d, 11)))->toBeFalse();
        }
    });

    it('accepts a generated PIS', function (): void {
        expect(Pis::isValid(Pis::generate()))->toBeTrue();
    })->repeat(100);
});

describe('Pis::generate', function (): void {
    it('returns 11-character strings', function (): void {
        expect(Pis::generate())->toHaveLength(11);
    });

    it('returns numeric-only strings', function (): void {
        expect(Pis::generate())->toMatch('/^\d{11}$/');
    });
});

describe('Pis::format', function (): void {
    it('formats a raw 11-digit PIS in canonical mask', function (): void {
        expect(Pis::format('12065328705'))->toBe('120.65328.70-5');
    });

    it('reformats a masked PIS idempotently', function (): void {
        expect(Pis::format('120.65328.70-5'))->toBe('120.65328.70-5');
    });

    it('returns input unchanged when payload is not 11 digits', function (): void {
        expect(Pis::format('123'))->toBe('123');
        expect(Pis::format('abc'))->toBe('abc');
        expect(Pis::format('1206532870A'))->toBe('1206532870A');
    });

    it('does not validate the check digit — applies mask to any shape-valid input', function (): void {
        expect(Pis::format('12065328704'))->toBe('120.65328.70-4');
    });
});

describe('Pis::normalize', function (): void {
    it('removes mask characters', function (): void {
        expect(Pis::normalize('120.65328.70-5'))->toBe('12065328705');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Pis::normalize(' 120.65328.70-5 '))->toBe('12065328705');
    });

    it('preserves a raw PIS', function (): void {
        expect(Pis::normalize('12065328705'))->toBe('12065328705');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Pis::normalize(''))->toBe('');
    });
});
