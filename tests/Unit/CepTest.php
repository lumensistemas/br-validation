<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Cep;

covers(Cep::class);

describe('Cep::isValid', function (): void {
    it('accepts valid CEPs', function (mixed $value): void {
        expect(Cep::isValid($value))->toBeTrue();
    })->with([
        'raw' => ['01310100'],
        'masked' => ['01310-100'],
        'masked with whitespace' => [' 01310-100 '],
        'leading zeros' => ['00100000'],
        'all-equal sequence accepted (no checksum)' => ['11111111'],
    ]);

    it('rejects invalid CEPs', function (mixed $value): void {
        expect(Cep::isValid($value))->toBeFalse();
    })->with([
        'too short' => ['0131010'],
        'too long' => ['013101000'],
        'contains letter' => ['0131010A'],
        'non-string integer' => [1310100],
        'non-string array' => [['value']],
        'empty string' => [''],
    ]);

    it('accepts a generated CEP', function (): void {
        expect(Cep::isValid(Cep::generate()))->toBeTrue();
    })->repeat(100);
});

describe('Cep::generate', function (): void {
    it('returns 8-character strings', function (): void {
        expect(Cep::generate())->toHaveLength(8);
    });

    it('returns numeric-only strings', function (): void {
        expect(Cep::generate())->toMatch('/^\d{8}$/');
    });
});

describe('Cep::format', function (): void {
    it('formats a raw 8-digit CEP in canonical mask', function (): void {
        expect(Cep::format('01310100'))->toBe('01310-100');
    });

    it('reformats a masked CEP idempotently', function (): void {
        expect(Cep::format('01310-100'))->toBe('01310-100');
    });

    it('returns input unchanged when payload is not 8 digits', function (): void {
        expect(Cep::format('123'))->toBe('123');
        expect(Cep::format('abc'))->toBe('abc');
        expect(Cep::format('0131010A'))->toBe('0131010A');
    });
});

describe('Cep::normalize', function (): void {
    it('removes mask characters', function (): void {
        expect(Cep::normalize('01310-100'))->toBe('01310100');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Cep::normalize(' 01310-100 '))->toBe('01310100');
    });

    it('preserves a raw CEP', function (): void {
        expect(Cep::normalize('01310100'))->toBe('01310100');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Cep::normalize(''))->toBe('');
    });
});
