<?php

declare(strict_types=1);

namespace LumenSistemas\BrValidation;

/**
 * Chave PIX validator and type detector.
 *
 * A chave PIX (BACEN's PIX key) is one of five distinct shapes:
 *
 * - {@see self::TYPE_CPF} — an 11-digit CPF.
 * - {@see self::TYPE_CNPJ} — a 14-character CNPJ (numeric or
 *   alphanumeric per the 2026 rules).
 * - {@see self::TYPE_EMAIL} — an RFC 5322 e-mail address (BACEN
 *   caps key length at 77 characters; longer addresses are
 *   rejected even if otherwise valid).
 * - {@see self::TYPE_PHONE} — an E.164 phone number starting with
 *   `+55` followed by 10–11 digits (DDD + 8-digit landline or
 *   9-digit mobile).
 * - {@see self::TYPE_EVP} — a "chave aleatória", a random UUID
 *   v4 in canonical hyphenated form.
 *
 * This class is a thin dispatcher: CPF and CNPJ delegate to
 * {@see Cpf} and {@see Cnpj}; the other three shapes are
 * checked against shape patterns. No `format()` method is
 * exposed because the canonical form depends on the type and is
 * better produced by the type-specific class
 * ({@see Cpf::format()}, {@see Cnpj::format()}, …) when callers
 * actually need display formatting.
 */
final class Pix
{
    public const string TYPE_CPF = 'cpf';

    public const string TYPE_CNPJ = 'cnpj';

    public const string TYPE_EMAIL = 'email';

    public const string TYPE_PHONE = 'phone';

    public const string TYPE_EVP = 'evp';

    private const string PHONE_PATTERN = '/^\+55\d{10,11}$/';

    private const string EVP_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    private const int EMAIL_MAX_LENGTH = 77;

    /**
     * Checks whether the value is a syntactically valid chave
     * PIX of any supported type.
     *
     * Accepts any input type — non-string values return false
     * rather than raising, so callers can pass user input or
     * unknown payloads without try/catch.
     */
    public static function isValid(mixed $value): bool
    {
        return self::type($value) !== null;
    }

    /**
     * Returns the detected chave PIX type ({@see self::TYPE_CPF},
     * {@see self::TYPE_CNPJ}, {@see self::TYPE_EMAIL},
     * {@see self::TYPE_PHONE}, {@see self::TYPE_EVP}) or `null`
     * if the value matches no supported shape.
     *
     * Type detection runs in shape-specificity order: EVP (the
     * UUID shape is the most distinctive), then phone (must
     * start with `+`), then email (must contain `@`), then CPF
     * and CNPJ. The five shapes are mutually exclusive, so the
     * order matters only for short-circuit efficiency.
     */
    public static function type(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match(self::EVP_PATTERN, $trimmed) === 1) {
            return self::TYPE_EVP;
        }

        if (preg_match(self::PHONE_PATTERN, $trimmed) === 1) {
            return self::TYPE_PHONE;
        }

        if (str_contains($trimmed, '@')) {
            if (mb_strlen($trimmed) > self::EMAIL_MAX_LENGTH) {
                return null;
            }

            return filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false
                ? self::TYPE_EMAIL
                : null;
        }

        if (Cpf::isValid($trimmed)) {
            return self::TYPE_CPF;
        }

        if (Cnpj::isValid($trimmed)) {
            return self::TYPE_CNPJ;
        }

        return null;
    }

    /**
     * Normalizes a chave PIX to its BACEN storage form:
     *
     * - CPF / CNPJ: mask characters stripped (delegates to
     *   {@see Cpf::normalize()} / {@see Cnpj::normalize()}).
     * - E-mail / EVP: lowercased.
     * - Phone: passed through (already in E.164).
     *
     * Tolerant: when the input is not a recognized chave PIX
     * shape, the trimmed input is returned unchanged rather
     * than raising. Callers needing strict behaviour should
     * gate on {@see self::isValid()} first.
     */
    public static function normalize(string $value): string
    {
        $trimmed = trim($value);
        $type = self::type($trimmed);

        return match ($type) {
            self::TYPE_CPF => Cpf::normalize($trimmed),
            self::TYPE_CNPJ => Cnpj::normalize($trimmed),
            self::TYPE_EMAIL, self::TYPE_EVP => mb_strtolower($trimmed),
            self::TYPE_PHONE => $trimmed,
            default => $trimmed,
        };
    }

    /**
     * Generates a valid EVP chave PIX (UUID v4) — intended for
     * tests and seeders.
     *
     * Only EVPs can be generated without external context; the
     * other four shapes require a real CPF/CNPJ, e-mail, or
     * phone number that belongs to the key holder.
     */
    public static function generateEvp(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 12, 4),
            mb_substr($hex, 16, 4),
            mb_substr($hex, 20, 12),
        );
    }
}
