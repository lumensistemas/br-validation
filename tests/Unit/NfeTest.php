<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Nfe;

covers(Nfe::class);

describe('Nfe::isValid', function (): void {
    it('accepts valid access keys', function (mixed $value): void {
        expect(Nfe::isValid($value))->toBeTrue();
    })->with([
        'raw' => ['35240111222333000181550010000000011123456788'],
        'masked' => ['3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788'],
        'masked with surrounding whitespace' => [' 3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788 '],
        'dotted' => ['3524.0111.2223.3300.0181.5500.1000.0000.0111.2345.6788'],
        'NFC-e modelo 65' => ['35240111222333000181650010000000011123456780'],
    ]);

    it('rejects invalid access keys', function (mixed $value): void {
        expect(Nfe::isValid($value))->toBeFalse();
    })->with([
        'wrong check digit' => ['35240111222333000181550010000000011123456780'],
        'flipped interior digit' => ['35240111222333000181550010000000011123457788'],
        'too short' => ['3524011122233300018155001000000001112345678'],
        'too long' => ['352401112223330001815500100000000111234567880'],
        'contains letter' => ['3524011122233300018155001000000001112345678A'],
        'non-string integer' => [35240111222333000181550010000000011123456788],
        'non-string array' => [['value']],
        'empty string' => [''],
    ]);

    it('rejects all-equal-digit sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(Nfe::isValid(str_repeat((string) $d, 44)))->toBeFalse();
        }
    });

    it('accepts a generated access key', function (): void {
        expect(Nfe::isValid(Nfe::generate()))->toBeTrue();
    })->repeat(100);
});

describe('Nfe::generate', function (): void {
    it('returns 44-character strings', function (): void {
        expect(Nfe::generate())->toHaveLength(44);
    });

    it('returns numeric-only strings', function (): void {
        expect(Nfe::generate())->toMatch('/^\d{44}$/');
    });
});

describe('Nfe::format', function (): void {
    it('formats a raw access key in canonical mask', function (): void {
        expect(Nfe::format('35240111222333000181550010000000011123456788'))
            ->toBe('3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788');
    });

    it('reformats a masked access key idempotently', function (): void {
        $masked = '3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788';
        expect(Nfe::format($masked))->toBe($masked);
    });

    it('returns input unchanged when payload is not 44 digits', function (): void {
        expect(Nfe::format('123'))->toBe('123');
        expect(Nfe::format('abc'))->toBe('abc');
        expect(Nfe::format(str_repeat('1', 43).'A'))->toBe(str_repeat('1', 43).'A');
    });

    it('does not validate the check digit — applies mask to any shape-valid input', function (): void {
        expect(Nfe::format('35240111222333000181550010000000011123456780'))
            ->toBe('3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6780');
    });
});

describe('Nfe::normalize', function (): void {
    it('removes mask whitespace', function (): void {
        expect(Nfe::normalize('3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788'))
            ->toBe('35240111222333000181550010000000011123456788');
    });

    it('removes dots', function (): void {
        expect(Nfe::normalize('3524.0111.2223.3300.0181.5500.1000.0000.0111.2345.6788'))
            ->toBe('35240111222333000181550010000000011123456788');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Nfe::normalize(' 35240111222333000181550010000000011123456788 '))
            ->toBe('35240111222333000181550010000000011123456788');
    });

    it('preserves a raw access key', function (): void {
        expect(Nfe::normalize('35240111222333000181550010000000011123456788'))
            ->toBe('35240111222333000181550010000000011123456788');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Nfe::normalize(''))->toBe('');
    });
});
