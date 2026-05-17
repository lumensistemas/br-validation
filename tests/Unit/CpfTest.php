<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Cpf;

covers(Cpf::class);

describe('Cpf::isValid', function (): void {
    it('accepts valid CPFs', function (mixed $value): void {
        expect(Cpf::isValid($value))->toBeTrue();
    })->with([
        'masked' => ['856.981.040-77'],
        'masked with zero check digit' => ['630.855.000-06'],
        'another masked' => ['299.635.220-33'],
        'masked with whitespace' => [' 856.981.040-77 '],
        'unmasked' => ['19357075070'],
        'another unmasked' => ['84250974014'],
        'DV1 of nine' => ['012.345.678-90'],
    ]);

    it('rejects invalid CPFs', function (mixed $value): void {
        expect(Cpf::isValid($value))->toBeFalse();
    })->with([
        'all zeros' => ['00000000000'],
        'all ones' => ['11111111111'],
        'all nines' => ['99999999999'],
        'too short' => ['1234567890'],
        'too long' => ['123456789012'],
        'wrong first digit' => ['52998224735'],
        'wrong DV1 with coincidental DV2 match' => ['11144477700'],
        'letters' => ['abcdefghijk'],
        'non-string integer' => [12345678909],
        'non-string array' => [['value']],
        'empty string' => [''],
        'masked all equal' => ['111.111.111-11'],
        'wrong second digit' => ['52998224726'],
    ]);

    it('accepts a generated CPF', function (): void {
        expect(Cpf::isValid(Cpf::generate()))->toBeTrue();
    })->repeat(100);
});

describe('Cpf::generate', function (): void {
    it('returns 11-character strings', function (): void {
        expect(Cpf::generate())->toHaveLength(11);
    });

    it('returns numeric-only strings', function (): void {
        expect(Cpf::generate())->toMatch('/^\d{11}$/');
    });
});

describe('Cpf::format', function (): void {
    it('formats a raw 11-digit CPF in canonical mask', function (): void {
        expect(Cpf::format('11144477735'))->toBe('111.444.777-35');
    });

    it('reformats a masked CPF idempotently', function (): void {
        expect(Cpf::format('111.444.777-35'))->toBe('111.444.777-35');
    });

    it('returns input unchanged when payload is not 11 digits', function (): void {
        expect(Cpf::format('123'))->toBe('123');
        expect(Cpf::format('abc'))->toBe('abc');
        expect(Cpf::format('1114447773A'))->toBe('1114447773A');
    });
});

describe('Cpf::normalize', function (): void {
    it('removes mask characters', function (): void {
        expect(Cpf::normalize('111.444.777-35'))->toBe('11144477735');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Cpf::normalize(' 111.444.777-35 '))->toBe('11144477735');
    });

    it('preserves a raw CPF', function (): void {
        expect(Cpf::normalize('11144477735'))->toBe('11144477735');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Cpf::normalize(''))->toBe('');
    });
});
