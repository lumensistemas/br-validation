<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\TituloEleitor;

covers(TituloEleitor::class);

describe('TituloEleitor::isValid', function (): void {
    it('accepts valid titles', function (mixed $value): void {
        expect(TituloEleitor::isValid($value))->toBeTrue();
    })->with([
        'RJ raw' => ['123456780396'],
        'SP raw' => ['123456780191'],
        'ZZ exterior' => ['123456782895'],
        'SP special DV1 (remainder 0 -> 1)' => ['100000010116'],
        'MG special DV1 (remainder 0 -> 1)' => ['100000010213'],
        'non-SP/MG remainder 0 -> DV 0' => ['100000010302'],
        'masked' => ['1234 5678 0396'],
        'masked with surrounding whitespace' => [' 1234 5678 0396 '],
    ]);

    it('rejects invalid titles', function (mixed $value): void {
        expect(TituloEleitor::isValid($value))->toBeFalse();
    })->with([
        'wrong DV2' => ['123456780397'],
        'wrong DV1' => ['123456780386'],
        'invalid UF 00' => ['123456780005'],
        'invalid UF 29' => ['123456782905'],
        'invalid UF 99' => ['123456789905'],
        'too short' => ['12345678039'],
        'too long' => ['1234567803960'],
        'contains letter' => ['12345678039A'],
        'non-string integer' => [123456780396],
        'non-string array' => [['value']],
        'empty string' => [''],
        'masked all equal' => ['1111 1111 1111'],
        'SP without special bump' => ['100000010106'],
    ]);

    it('rejects all-equal-digit sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(TituloEleitor::isValid(str_repeat((string) $d, 12)))->toBeFalse();
        }
    });

    it('accepts a generated title', function (): void {
        expect(TituloEleitor::isValid(TituloEleitor::generate()))->toBeTrue();
    })->repeat(200);
});

describe('TituloEleitor::generate', function (): void {
    it('returns 12-character strings', function (): void {
        expect(TituloEleitor::generate())->toHaveLength(12);
    });

    it('returns numeric-only strings', function (): void {
        expect(TituloEleitor::generate())->toMatch('/^\d{12}$/');
    });

    it('always picks a UF in 01..28', function (): void {
        $uf = (int) mb_substr(TituloEleitor::generate(), 8, 2);
        expect($uf)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(28);
    })->repeat(50);
});

describe('TituloEleitor::format', function (): void {
    it('formats a raw title in canonical mask', function (): void {
        expect(TituloEleitor::format('123456780396'))->toBe('1234 5678 0396');
    });

    it('reformats a masked title idempotently', function (): void {
        expect(TituloEleitor::format('1234 5678 0396'))->toBe('1234 5678 0396');
    });

    it('returns input unchanged when payload is not 12 digits', function (): void {
        expect(TituloEleitor::format('123'))->toBe('123');
        expect(TituloEleitor::format('abc'))->toBe('abc');
        expect(TituloEleitor::format('12345678039A'))->toBe('12345678039A');
    });

    it('does not validate check digits or UF — applies mask to any shape-valid input', function (): void {
        expect(TituloEleitor::format('123456789905'))->toBe('1234 5678 9905');
    });
});

describe('TituloEleitor::normalize', function (): void {
    it('removes mask whitespace', function (): void {
        expect(TituloEleitor::normalize('1234 5678 0396'))->toBe('123456780396');
    });

    it('does not strip dots or hyphens (not in canonical mask)', function (): void {
        expect(TituloEleitor::normalize('1234.5678.0396'))->toBe('1234.5678.0396');
        expect(TituloEleitor::normalize('1234-5678-0396'))->toBe('1234-5678-0396');
    });

    it('strips surrounding whitespace', function (): void {
        expect(TituloEleitor::normalize(' 123456780396 '))->toBe('123456780396');
    });

    it('preserves a raw title', function (): void {
        expect(TituloEleitor::normalize('123456780396'))->toBe('123456780396');
    });

    it('returns an empty string unchanged', function (): void {
        expect(TituloEleitor::normalize(''))->toBe('');
    });
});
