<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Placa;

covers(Placa::class);

describe('Placa::isValid', function (): void {
    it('accepts valid placas', function (mixed $value): void {
        expect(Placa::isValid($value))->toBeTrue();
    })->with([
        'old raw' => ['ABC1234'],
        'old hyphenated' => ['ABC-1234'],
        'old lowercase' => ['abc1234'],
        'old with whitespace' => [' ABC-1234 '],
        'mercosul raw' => ['ABC1D23'],
        'mercosul lowercase' => ['abc1d23'],
        'mercosul hyphenated' => ['ABC-1D23'],
        'mercosul with whitespace' => [' abc1d23 '],
    ]);

    it('rejects invalid placas', function (mixed $value): void {
        expect(Placa::isValid($value))->toBeFalse();
    })->with([
        'too short' => ['ABC123'],
        'too long' => ['ABC12345'],
        'four leading letters' => ['ABCD123'],
        'five trailing digits' => ['AB12345'],
        'digit in letter position 0' => ['1BC1234'],
        'mercosul-ish with letter at pos 4 wrong' => ['ABCA1D23'],
        'special character' => ['ABC@1234'],
        'non-string integer' => [1231234],
        'empty string' => [''],
    ]);

    it('accepts a generated old placa', function (): void {
        $p = Placa::generateOld();
        expect(Placa::isValid($p))->toBeTrue();
        expect($p)->toMatch('/^[A-Z]{3}\d{4}$/');
    })->repeat(100);

    it('accepts a generated mercosul placa', function (): void {
        $p = Placa::generateMercosul();
        expect(Placa::isValid($p))->toBeTrue();
        expect($p)->toMatch('/^[A-Z]{3}\d[A-Z]\d{2}$/');
    })->repeat(100);
});

describe('Placa::generateOld', function (): void {
    it('returns 7-character strings', function (): void {
        expect(Placa::generateOld())->toHaveLength(7);
    });
});

describe('Placa::generateMercosul', function (): void {
    it('returns 7-character strings', function (): void {
        expect(Placa::generateMercosul())->toHaveLength(7);
    });
});

describe('Placa::format', function (): void {
    it('formats old placa with hyphen', function (): void {
        expect(Placa::format('ABC1234'))->toBe('ABC-1234');
    });

    it('keeps Mercosul placa without separator', function (): void {
        expect(Placa::format('ABC1D23'))->toBe('ABC1D23');
        expect(Placa::format('ABC-1D23'))->toBe('ABC1D23');
    });

    it('uppercases letters before formatting', function (): void {
        expect(Placa::format('abc1234'))->toBe('ABC-1234');
        expect(Placa::format('abc1d23'))->toBe('ABC1D23');
    });

    it('reformats an old hyphenated placa idempotently', function (): void {
        expect(Placa::format('ABC-1234'))->toBe('ABC-1234');
    });

    it('returns input unchanged when payload matches neither shape', function (): void {
        expect(Placa::format('123'))->toBe('123');
        expect(Placa::format('ABC@1234'))->toBe('ABC@1234');
    });
});

describe('Placa::normalize', function (): void {
    it('removes whitespace and hyphens', function (): void {
        expect(Placa::normalize(' ABC-1234 '))->toBe('ABC1234');
    });

    it('uppercases letters', function (): void {
        expect(Placa::normalize('abc1d23'))->toBe('ABC1D23');
    });

    it('preserves a raw placa', function (): void {
        expect(Placa::normalize('ABC1234'))->toBe('ABC1234');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Placa::normalize(''))->toBe('');
    });
});
