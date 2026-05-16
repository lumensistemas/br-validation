<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Cnpj;

covers(Cnpj::class);

describe('Cnpj::isValid', function (): void {
    it('accepts valid CNPJs', function (mixed $value): void {
        expect(Cnpj::isValid($value))->toBeTrue();
    })->with([
        'numeric' => ['11222333000181'],
        'masked' => ['11.222.333/0001-81'],
        'masked with whitespace' => [' 11.222.333/0001-81 '],
        'all zeros prefix masked' => ['00.000.000/0001-91'],
        'all zeros prefix' => ['00000000000191'],
        'DV1 of zero' => ['00.000.000/0099-03'],
        'alphanumeric masked lowercase' => ['12.abc.345/01de-35'],
        'alphanumeric' => ['12abc34501de35'],
        'alphanumeric uppercase' => ['12ABC34501DE35'],
    ]);

    it('rejects invalid CNPJs', function (mixed $value): void {
        expect(Cnpj::isValid($value))->toBeFalse();
    })->with([
        'wrong first verification digit' => ['11222333000171'],
        'wrong DV1 with coincidental DV2 match' => ['11222333000106'],
        'wrong check digits' => ['11222333000182'],
        'alphanumeric wrong DV2' => ['12ABC34501DE34'],
        'too short' => ['1122233300018'],
        'too long' => ['112223330001811'],
        'non-string integer' => [11222333000181],
        'empty string' => [''],
        'letters only' => ['abcdefghijklmn'],
    ]);

    it('rejects all-equal-character sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(Cnpj::isValid(str_repeat((string) $d, 14)))->toBeFalse();
        }
        expect(Cnpj::isValid(str_repeat('A', 14)))->toBeFalse();
    });

    it('accepts a generated numeric CNPJ', function (): void {
        expect(Cnpj::isValid(Cnpj::generateNumeric()))->toBeTrue();
    })->repeat(100);

    it('accepts a generated alphanumeric CNPJ', function (): void {
        expect(Cnpj::isValid(Cnpj::generateAlphanumeric()))->toBeTrue();
    })->repeat(100);
});

describe('Cnpj::generateNumeric', function (): void {
    it('returns 14-character strings', function (): void {
        expect(Cnpj::generateNumeric())->toHaveLength(14);
    });

    it('returns numeric-only strings', function (): void {
        expect(Cnpj::generateNumeric())->toMatch('/^\d{14}$/');
    });
});

describe('Cnpj::generateAlphanumeric', function (): void {
    it('returns 14-character strings', function (): void {
        expect(Cnpj::generateAlphanumeric())->toHaveLength(14);
    });

    it('returns strings matching the alphanumeric CNPJ shape', function (): void {
        expect(Cnpj::generateAlphanumeric())->toMatch('/^[A-Z0-9]{12}\d{2}$/');
    });
});

describe('Cnpj::format', function (): void {
    it('formats a raw numeric CNPJ in canonical mask', function (): void {
        expect(Cnpj::format('11222333000181'))->toBe('11.222.333/0001-81');
    });

    it('formats a raw alphanumeric CNPJ in canonical mask', function (): void {
        $cnpj = Cnpj::generateAlphanumeric();
        $formatted = Cnpj::format($cnpj);

        expect($formatted)->toMatch('/^[A-Z0-9]{2}\.[A-Z0-9]{3}\.[A-Z0-9]{3}\/[A-Z0-9]{4}-\d{2}$/');
        expect(Cnpj::normalize($formatted))->toBe($cnpj);
    });

    it('reformats a masked CNPJ idempotently', function (): void {
        expect(Cnpj::format('11.222.333/0001-81'))->toBe('11.222.333/0001-81');
    });

    it('uppercases letters before applying the mask', function (): void {
        expect(Cnpj::format('12abc34501de35'))->toBe('12.ABC.345/01DE-35');
        expect(Cnpj::format('12.abc.345/01de-35'))->toBe('12.ABC.345/01DE-35');
    });

    it('returns input unchanged when payload is not 14 valid characters', function (): void {
        expect(Cnpj::format('123'))->toBe('123');
        expect(Cnpj::format('abc'))->toBe('abc');
        expect(Cnpj::format('1122233300018A'))->toBe('1122233300018A');
    });

    it('does not validate check digits — applies mask to any shape-valid input', function (): void {
        expect(Cnpj::format('11222333000182'))->toBe('11.222.333/0001-82');
    });
});

describe('Cnpj::normalize', function (): void {
    it('removes mask characters', function (): void {
        expect(Cnpj::normalize('11.222.333/0001-81'))->toBe('11222333000181');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Cnpj::normalize(' 11.222.333/0001-81 '))->toBe('11222333000181');
    });

    it('uppercases letters', function (): void {
        expect(Cnpj::normalize('12.abc.345/01de-35'))->toBe('12ABC34501DE35');
    });

    it('preserves a raw CNPJ', function (): void {
        expect(Cnpj::normalize('11222333000181'))->toBe('11222333000181');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Cnpj::normalize(''))->toBe('');
    });
});
