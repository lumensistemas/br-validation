<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Renavam;

covers(Renavam::class);

describe('Renavam::isValid', function (): void {
    it('accepts valid Renavams', function (mixed $value): void {
        expect(Renavam::isValid($value))->toBeTrue();
    })->with([
        'ascending base' => ['01234567897'],
        'DV of zero' => ['12345678900'],
        'descending base' => ['98765432103'],
        'mostly zeros' => ['00000000019'],
        'whitespace stripped' => [' 01234567897 '],
        'hyphen separator' => ['0123456789-7'],
    ]);

    it('rejects invalid Renavams', function (mixed $value): void {
        expect(Renavam::isValid($value))->toBeFalse();
    })->with([
        'wrong check digit' => ['01234567898'],
        'too short' => ['0123456789'],
        'too long' => ['012345678970'],
        'contains letter' => ['0123456789A'],
        'non-string integer' => [1234567897],
        'empty string' => [''],
        'all zeros' => ['00000000000'],
    ]);

    it('rejects all-equal-digit sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(Renavam::isValid(str_repeat((string) $d, 11)))->toBeFalse();
        }
    });

    it('accepts a generated Renavam', function (): void {
        expect(Renavam::isValid(Renavam::generate()))->toBeTrue();
    })->repeat(200);
});

describe('Renavam::generate', function (): void {
    it('returns 11-character strings', function (): void {
        expect(Renavam::generate())->toHaveLength(11);
    });

    it('returns numeric-only strings', function (): void {
        expect(Renavam::generate())->toMatch('/^\d{11}$/');
    });
});

describe('Renavam::format', function (): void {
    it('returns the raw 11-digit form', function (): void {
        expect(Renavam::format('01234567897'))->toBe('01234567897');
    });

    it('strips mask characters before returning the raw form', function (): void {
        expect(Renavam::format(' 0123456789-7 '))->toBe('01234567897');
    });

    it('returns input unchanged when payload is not 11 digits', function (): void {
        expect(Renavam::format('123'))->toBe('123');
        expect(Renavam::format('abc'))->toBe('abc');
    });
});

describe('Renavam::normalize', function (): void {
    it('removes mask characters', function (): void {
        expect(Renavam::normalize('012.345.678.9-7'))->toBe('01234567897');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Renavam::normalize(' 01234567897 '))->toBe('01234567897');
    });

    it('preserves a raw Renavam', function (): void {
        expect(Renavam::normalize('01234567897'))->toBe('01234567897');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Renavam::normalize(''))->toBe('');
    });
});
