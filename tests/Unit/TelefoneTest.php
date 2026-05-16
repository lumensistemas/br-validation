<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Telefone;

covers(Telefone::class);

describe('Telefone::isValid', function (): void {
    it('accepts valid phone numbers', function (mixed $value): void {
        expect(Telefone::isValid($value))->toBeTrue();
    })->with([
        'mobile raw' => ['11987654321'],
        'mobile masked' => ['(11) 98765-4321'],
        'mobile masked alt' => ['11 98765-4321'],
        'mobile E.164' => ['+5511987654321'],
        'landline raw' => ['1133334444'],
        'landline masked' => ['(11) 3333-4444'],
        'landline E.164' => ['+551133334444'],
        'with surrounding whitespace' => [' (11) 98765-4321 '],
        'DDD 99 mobile' => ['99987654321'],
    ]);

    it('rejects invalid phone numbers', function (mixed $value): void {
        expect(Telefone::isValid($value))->toBeFalse();
    })->with([
        'too short' => ['1198765432'],
        'too long' => ['119876543210'],
        'DDD starts with 0' => ['0198765432'],
        'DDD ends with 0' => ['1098765432'],
        'mobile without 9 prefix' => ['11187654321'],
        'landline starting with 9' => ['1198765432'],
        '9-digit number with 10-digit DDD-less call' => ['987654321'],
        'non-Brazilian country code' => ['+12025550100'],
        'empty string' => [''],
        'letters only' => ['abcdefghijk'],
        'non-string integer' => [11987654321],
    ]);

    it('accepts a generated mobile', function (): void {
        expect(Telefone::isValid(Telefone::generateMobile()))->toBeTrue();
    })->repeat(100);

    it('accepts a generated landline', function (): void {
        expect(Telefone::isValid(Telefone::generateLandline()))->toBeTrue();
    })->repeat(100);
});

describe('Telefone::generateMobile', function (): void {
    it('returns 11-digit numbers starting with a 9 after the DDD', function (): void {
        $n = Telefone::generateMobile();
        expect($n)->toHaveLength(11)->toMatch('/^[1-9][1-9]9\d{8}$/');
    })->repeat(50);
});

describe('Telefone::generateLandline', function (): void {
    it('returns 10-digit numbers not starting with 9 after the DDD', function (): void {
        $n = Telefone::generateLandline();
        expect($n)->toHaveLength(10)->toMatch('/^[1-9][1-9][2-5]\d{7}$/');
    })->repeat(50);
});

describe('Telefone::format', function (): void {
    it('formats a mobile in (DD) XXXXX-XXXX', function (): void {
        expect(Telefone::format('11987654321'))->toBe('(11) 98765-4321');
    });

    it('formats a landline in (DD) XXXX-XXXX', function (): void {
        expect(Telefone::format('1133334444'))->toBe('(11) 3333-4444');
    });

    it('strips E.164 prefix before formatting', function (): void {
        expect(Telefone::format('+5511987654321'))->toBe('(11) 98765-4321');
    });

    it('reformats a masked phone idempotently', function (): void {
        expect(Telefone::format('(11) 98765-4321'))->toBe('(11) 98765-4321');
    });

    it('returns input unchanged when payload is not 10 or 11 digits', function (): void {
        expect(Telefone::format('123'))->toBe('123');
        expect(Telefone::format('abc'))->toBe('abc');
    });
});

describe('Telefone::formatE164', function (): void {
    it('formats a mobile in E.164', function (): void {
        expect(Telefone::formatE164('11987654321'))->toBe('+5511987654321');
    });

    it('formats a landline in E.164', function (): void {
        expect(Telefone::formatE164('1133334444'))->toBe('+551133334444');
    });

    it('idempotent on E.164 input', function (): void {
        expect(Telefone::formatE164('+5511987654321'))->toBe('+5511987654321');
    });

    it('strips mask characters before formatting', function (): void {
        expect(Telefone::formatE164('(11) 98765-4321'))->toBe('+5511987654321');
    });

    it('returns input unchanged when payload is not 10 or 11 digits', function (): void {
        expect(Telefone::formatE164('123'))->toBe('123');
    });
});

describe('Telefone::normalize', function (): void {
    it('strips mask characters', function (): void {
        expect(Telefone::normalize('(11) 98765-4321'))->toBe('11987654321');
    });

    it('strips +55 country code', function (): void {
        expect(Telefone::normalize('+5511987654321'))->toBe('11987654321');
    });

    it('strips bare + when no country code', function (): void {
        expect(Telefone::normalize('+11987654321'))->toBe('11987654321');
    });

    it('preserves bare 55 prefix (DDD ambiguity)', function (): void {
        expect(Telefone::normalize('5511987654321'))->toBe('5511987654321');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Telefone::normalize(' 11987654321 '))->toBe('11987654321');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Telefone::normalize(''))->toBe('');
    });
});
