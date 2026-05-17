<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Cns;

covers(Cns::class);

describe('Cns::isValid', function (): void {
    it('accepts valid CNSs', function (mixed $value): void {
        expect(Cns::isValid($value))->toBeTrue();
    })->with([
        'definitive 000 appendix' => ['120653287050007'],
        'definitive 001 appendix (DV bump path)' => ['100000000060018'],
        'provisional 7' => ['700000000000005'],
        'provisional 8' => ['800000000000001'],
        'provisional 9' => ['900000000000008'],
        'masked' => ['120 6532 8705 0007'],
        'masked with surrounding whitespace' => [' 120 6532 8705 0007 '],
    ]);

    it('rejects invalid CNSs', function (mixed $value): void {
        expect(Cns::isValid($value))->toBeFalse();
    })->with([
        'wrong check digit' => ['120653287050008'],
        'definitive with 002 appendix (mod-11 ok, structure rejected)' => ['100000000060026'],
        'definitive with 100 appendix (mod-11 ok, structure rejected)' => ['100000000061006'],
        'first digit 0' => ['000653287050007'],
        'first digit 3' => ['320653287050007'],
        'first digit 6' => ['620653287050007'],
        'too short' => ['12065328705000'],
        'too long' => ['1206532870500070'],
        'contains letter' => ['12065328705000A'],
        'non-string integer' => [120653287050007],
        'empty string' => [''],
    ]);

    it('rejects all-equal-digit sequences', function (): void {
        foreach (range(0, 9) as $d) {
            expect(Cns::isValid(str_repeat((string) $d, 15)))->toBeFalse();
        }
    });

    it('accepts a generated definitive CNS', function (): void {
        expect(Cns::isValid(Cns::generateDefinitive()))->toBeTrue();
    })->repeat(200);

    it('accepts a generated provisional CNS', function (): void {
        expect(Cns::isValid(Cns::generateProvisional()))->toBeTrue();
    })->repeat(200);
});

describe('Cns::generateDefinitive', function (): void {
    it('returns 15-character strings', function (): void {
        expect(Cns::generateDefinitive())->toHaveLength(15);
    });

    it('starts with 1 or 2', function (): void {
        $first = Cns::generateDefinitive()[0];
        expect($first === '1' || $first === '2')->toBeTrue();
    })->repeat(50);

    it('uses 000 or 001 in positions 12-14', function (): void {
        $appendix = mb_substr(Cns::generateDefinitive(), 11, 3);
        expect($appendix === '000' || $appendix === '001')->toBeTrue();
    })->repeat(50);
});

describe('Cns::generateProvisional', function (): void {
    it('returns 15-character strings', function (): void {
        expect(Cns::generateProvisional())->toHaveLength(15);
    });

    it('starts with 7, 8, or 9', function (): void {
        $first = Cns::generateProvisional()[0];
        expect(in_array($first, ['7', '8', '9'], true))->toBeTrue();
    })->repeat(50);
});

describe('Cns::format', function (): void {
    it('formats a raw CNS in canonical mask', function (): void {
        expect(Cns::format('120653287050007'))->toBe('120 6532 8705 0007');
    });

    it('reformats a masked CNS idempotently', function (): void {
        expect(Cns::format('120 6532 8705 0007'))->toBe('120 6532 8705 0007');
    });

    it('returns input unchanged when payload is not 15 digits', function (): void {
        expect(Cns::format('123'))->toBe('123');
        expect(Cns::format('abc'))->toBe('abc');
    });

    it('does not validate check digit or type — applies mask to any shape-valid input', function (): void {
        expect(Cns::format('120653287050008'))->toBe('120 6532 8705 0008');
    });
});

describe('Cns::normalize', function (): void {
    it('removes mask whitespace', function (): void {
        expect(Cns::normalize('120 6532 8705 0007'))->toBe('120653287050007');
    });

    it('does not strip dots or hyphens (not in canonical mask)', function (): void {
        expect(Cns::normalize('120.6532.8705.0007'))->toBe('120.6532.8705.0007');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Cns::normalize(' 120653287050007 '))->toBe('120653287050007');
    });

    it('preserves a raw CNS', function (): void {
        expect(Cns::normalize('120653287050007'))->toBe('120653287050007');
    });

    it('returns an empty string unchanged', function (): void {
        expect(Cns::normalize(''))->toBe('');
    });
});
