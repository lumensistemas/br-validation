<?php

declare(strict_types=1);

use LumenSistemas\BrValidation\Pix;

covers(Pix::class);

describe('Pix::isValid', function (): void {
    it('accepts valid keys of every type', function (mixed $value): void {
        expect(Pix::isValid($value))->toBeTrue();
    })->with([
        'cpf raw' => ['85698104077'],
        'cpf masked' => ['856.981.040-77'],
        'cnpj raw' => ['11222333000181'],
        'cnpj masked' => ['11.222.333/0001-81'],
        'cnpj alphanumeric' => ['12ABC34501DE35'],
        'email simple' => ['user@example.com'],
        'email with subaddress' => ['t.u+pix@a.co.br'],
        'phone mobile' => ['+5511987654321'],
        'phone landline' => ['+551133334444'],
        'evp lowercase' => ['123e4567-e89b-42d3-a456-426614174000'],
        'evp uppercase' => ['123E4567-E89B-42D3-A456-426614174000'],
        'evp variant b' => ['123e4567-e89b-42d3-b456-426614174000'],
        'with surrounding whitespace' => ['  user@example.com  '],
    ]);

    it('rejects invalid keys', function (mixed $value): void {
        expect(Pix::isValid($value))->toBeFalse();
    })->with([
        'empty string' => [''],
        'whitespace only' => ['   '],
        'random string' => ['not-a-key'],
        'cpf with wrong dv' => ['85698104078'],
        'cnpj with wrong dv' => ['11222333000182'],
        'phone without country code' => ['11987654321'],
        'phone with wrong country code' => ['+15511987654321'],
        'phone too short' => ['+5511987654'],
        'phone too long' => ['+550511987654321'],
        'evp wrong version' => ['123e4567-e89b-12d3-a456-426614174000'],
        'evp wrong variant' => ['123e4567-e89b-42d3-c456-426614174000'],
        'evp without hyphens' => ['123e4567e89b42d3a456426614174000'],
        'email missing local' => ['@example.com'],
        'email missing at' => ['userexample.com'],
        'email over 77 chars' => [str_repeat('a', 70).'@example.com'],
        'non-string integer' => [85698104077],
        'non-string array' => [['user@example.com']],
    ]);

    it('accepts a generated EVP', function (): void {
        expect(Pix::isValid(Pix::generateEvp()))->toBeTrue();
    })->repeat(100);
});

describe('Pix::type', function (): void {
    it('detects every supported type', function (string $value, string $expected): void {
        expect(Pix::type($value))->toBe($expected);
    })->with([
        'cpf' => ['85698104077', Pix::TYPE_CPF],
        'cnpj' => ['11222333000181', Pix::TYPE_CNPJ],
        'email' => ['user@example.com', Pix::TYPE_EMAIL],
        'phone' => ['+5511987654321', Pix::TYPE_PHONE],
        'evp' => ['123e4567-e89b-42d3-a456-426614174000', Pix::TYPE_EVP],
    ]);

    it('returns null for invalid inputs', function (mixed $value): void {
        expect(Pix::type($value))->toBeNull();
    })->with([
        'empty' => [''],
        'random' => ['not-a-key'],
        'non-string' => [42],
    ]);
});

describe('Pix::normalize', function (): void {
    it('strips CPF mask characters', function (): void {
        expect(Pix::normalize('856.981.040-77'))->toBe('85698104077');
    });

    it('strips CNPJ mask characters', function (): void {
        expect(Pix::normalize('11.222.333/0001-81'))->toBe('11222333000181');
    });

    it('lowercases email', function (): void {
        expect(Pix::normalize('Test.User+pix@Company.com.br'))->toBe('test.user+pix@company.com.br');
    });

    it('lowercases EVP', function (): void {
        expect(Pix::normalize('123E4567-E89B-42D3-A456-426614174000'))
            ->toBe('123e4567-e89b-42d3-a456-426614174000');
    });

    it('passes phone through unchanged', function (): void {
        expect(Pix::normalize('+5511987654321'))->toBe('+5511987654321');
    });

    it('strips surrounding whitespace', function (): void {
        expect(Pix::normalize('  user@example.com  '))->toBe('user@example.com');
    });

    it('returns trimmed input unchanged for unrecognised shapes', function (): void {
        expect(Pix::normalize('  garbage  '))->toBe('garbage');
    });
});

describe('Pix::generateEvp', function (): void {
    it('returns a 36-character UUID v4 string', function (): void {
        expect(Pix::generateEvp())->toHaveLength(36)
            ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    });

    it('detects as EVP type', function (): void {
        expect(Pix::type(Pix::generateEvp()))->toBe(Pix::TYPE_EVP);
    })->repeat(50);
});
