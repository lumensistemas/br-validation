<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Boleto;

covers(Boleto::class);

describe('Boleto::isValid', function (): void {
    it('accepts valid boletos', function (mixed $value): void {
        expect(Boleto::isValid($value))->toBeTrue();
    })->with([
        'linha digitável raw' => ['00190123435678901234356789012343199990000010000'],
        'linha digitável masked' => ['00190.12343 56789.012343 56789.012343 1 99990000010000'],
        'barcode raw' => ['00191999900000100000123456789012345678901234'],
        'linha with surrounding whitespace' => [' 00190123435678901234356789012343199990000010000 '],
    ]);

    it('rejects invalid boletos', function (mixed $value): void {
        expect(Boleto::isValid($value))->toBeFalse();
    })->with([
        'wrong field-1 DV' => ['00191123435678901234356789012343199990000010000'],
        'wrong field-2 DV' => ['00190123435678901234456789012343199990000010000'],
        'wrong general DV' => ['00190123435678901234356789012343299990000010000'],
        'barcode wrong general DV' => ['00192999900000100000123456789012345678901234'],
        'too short' => ['0019012343567890'],
        'too long' => ['001901234356789012343567890123431999900000100000'],
        '45 digits' => ['001901234356789012343567890123431999900000100'],
        'contains letter' => ['00190123435678901234356789012343199990000010A00'],
        'non-string integer' => [12345],
        'non-string array' => [['value']],
        'empty string' => [''],
    ]);

    it('accepts a generated boleto', function (): void {
        expect(Boleto::isValid(Boleto::generate()))->toBeTrue();
    })->repeat(200);
});

describe('Boleto::generate', function (): void {
    it('returns 47-character linha digitável strings', function (): void {
        expect(Boleto::generate())->toHaveLength(47);
    });

    it('returns numeric-only strings', function (): void {
        expect(Boleto::generate())->toMatch('/^\d{47}$/');
    });
});

describe('Boleto::format', function (): void {
    it('formats a linha digitável in canonical mask', function (): void {
        expect(Boleto::format('00190123435678901234356789012343199990000010000'))
            ->toBe('00190.12343 56789.012343 56789.012343 1 99990000010000');
    });

    it('reformats a masked linha digitável idempotently', function (): void {
        $masked = '00190.12343 56789.012343 56789.012343 1 99990000010000';
        expect(Boleto::format($masked))->toBe($masked);
    });

    it('returns barcode in normalized raw form', function (): void {
        expect(Boleto::format('00191999900000100000123456789012345678901234'))
            ->toBe('00191999900000100000123456789012345678901234');
    });

    it('returns input unchanged when payload matches no boleto shape', function (): void {
        expect(Boleto::format('123'))->toBe('123');
        expect(Boleto::format('abc'))->toBe('abc');
    });
});

describe('Boleto::normalize', function (): void {
    it('removes mask characters', function (): void {
        expect(Boleto::normalize('00190.12343 56789.012343 56789.012343 1 99990000010000'))
            ->toBe('00190123435678901234356789012343199990000010000');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Boleto::normalize(' 00190123435678901234356789012343199990000010000 '))
            ->toBe('00190123435678901234356789012343199990000010000');
    });

    it('preserves a raw boleto', function (): void {
        expect(Boleto::normalize('00190123435678901234356789012343199990000010000'))
            ->toBe('00190123435678901234356789012343199990000010000');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Boleto::normalize(''))->toBe('');
    });
});
