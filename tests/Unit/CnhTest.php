<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Cnh;

covers(Cnh::class);

describe('Cnh::isValid', function (): void {
    it('accepts valid CNHs', function (mixed $value): void {
        expect(Cnh::isValid($value))->toBeTrue();
    })->with([
        'ascending base' => ['12345678900'],
        'descending base with dsc wrap' => ['98765432109'],
        'mostly zeros' => ['10000000091'],
        'arbitrary' => ['23456789137'],
        'dsc wrap clamps DV2 from 10 to 0' => ['64575984700'],
        'whitespace stripped' => [' 23456789137 '],
        'hyphen separator' => ['234567891-37'],
    ]);

    it('rejects invalid CNHs', function (mixed $value): void {
        expect(Cnh::isValid($value))->toBeFalse();
    })->with([
        'wrong DV2' => ['12345678901'],
        'wrong DV1' => ['12345678910'],
        'dsc not applied' => ['98765432100'],
        'too short' => ['1234567890'],
        'too long' => ['123456789000'],
        'contains letter' => ['1234567890A'],
        'non-string integer' => [12345678900],
        'non-string array' => [['value']],
        'empty string' => [''],
    ]);

    it('rejects all-equal-digit sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(Cnh::isValid(str_repeat((string) $d, 11)))->toBeFalse();
        }
    });

    it('accepts a generated CNH', function (): void {
        expect(Cnh::isValid(Cnh::generate()))->toBeTrue();
    })->repeat(200);
});

describe('Cnh::generate', function (): void {
    it('returns 11-character strings', function (): void {
        expect(Cnh::generate())->toHaveLength(11);
    });

    it('returns numeric-only strings', function (): void {
        expect(Cnh::generate())->toMatch('/^\d{11}$/');
    });
});

describe('Cnh::normalize', function (): void {
    it('removes whitespace', function (): void {
        expect(Cnh::normalize(' 12345678900 '))->toBe('12345678900');
    });

    it('removes dots and hyphens', function (): void {
        expect(Cnh::normalize('123.456.789-00'))->toBe('12345678900');
    });

    it('preserves a raw CNH', function (): void {
        expect(Cnh::normalize('12345678900'))->toBe('12345678900');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Cnh::normalize(''))->toBe('');
    });
});
