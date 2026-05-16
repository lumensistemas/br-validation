# br-validation

[![Tests](https://github.com/lumensistemas/br-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/lumensistemas/br-validation/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/lumensistemas/br-validation.svg)](https://packagist.org/packages/lumensistemas/br-validation)
[![Total Downloads](https://img.shields.io/packagist/dt/lumensistemas/br-validation.svg)](https://packagist.org/packages/lumensistemas/br-validation)

Validators, generators, and formatters for Brazilian
identifiers (CPF, CNPJ, PIS, Título de Eleitor, CNH, and
NF-e access key) in PHP. Framework-agnostic and
dependency-free at runtime.

## Requirements

- PHP 8.3 or newer
- `ext-mbstring`

## Installation

```bash
composer require lumensistemas/br-validation
```

## Usage

### CPF

```php
use LumenSistemas\BrValidation\Cpf;

Cpf::isValid('856.981.040-77');   // true
Cpf::isValid('85698104077');      // true (raw form also accepted)
Cpf::isValid('11111111111');      // false (all-equal sequence)
Cpf::isValid(85698104077);        // false (non-string input)

Cpf::format('85698104077');       // '856.981.040-77'
Cpf::normalize('856.981.040-77'); // '85698104077'

Cpf::generate(); // a valid 11-digit CPF
```

### CNPJ

```php
use LumenSistemas\BrValidation\Cnpj;

Cnpj::isValid('11.222.333/0001-81');      // true (legacy numeric)
Cnpj::isValid('12ABC34501DE35');          // true (alphanumeric, 2026 rules)
Cnpj::isValid('12abc34501de35');          // true (case-insensitive)
Cnpj::isValid('00000000000000');          // false (all-equal sequence)

Cnpj::format('11222333000181');           // '11.222.333/0001-81'
Cnpj::format('12abc34501de35');           // '12.ABC.345/01DE-35'
Cnpj::normalize(' 11.222.333/0001-81 '); // '11222333000181'

Cnpj::generateNumeric();      // a valid 14-digit numeric CNPJ
Cnpj::generateAlphanumeric(); // a valid alphanumeric CNPJ
```

### PIS / PASEP / NIS / NIT

```php
use LumenSistemas\BrValidation\Pis;

Pis::isValid('120.65328.70-5'); // true
Pis::isValid('12065328705');    // true (raw form also accepted)
Pis::isValid('00000000000');    // false (all-equal sequence)
Pis::isValid(12065328705);      // false (non-string input)

Pis::format('12065328705');     // '120.65328.70-5'
Pis::normalize('120.65328.70-5'); // '12065328705'

Pis::generate(); // a valid 11-digit PIS
```

The same 11-digit number is issued under four different
government program names — PIS, PASEP, NIS, NIT — and
shares a single mod-11 check digit. `Pis` validates any of
them.

### Título de Eleitor

```php
use LumenSistemas\BrValidation\TituloEleitor;

TituloEleitor::isValid('1234 5678 0396'); // true (RJ)
TituloEleitor::isValid('123456780396');   // true (raw form also accepted)
TituloEleitor::isValid('123456789905');   // false (UF 99 is not a TSE code)
TituloEleitor::isValid('111111111111');   // false (all-equal sequence)

TituloEleitor::format('123456780396');    // '1234 5678 0396'
TituloEleitor::normalize('1234 5678 0396'); // '123456780396'

TituloEleitor::generate(); // a valid 12-digit título
```

The UF code in positions 9–10 is the TSE's own numbering
(`01..28`), not the IBGE UF code. São Paulo (`01`) and
Minas Gerais (`02`) follow a special rule: whenever a
check-digit calculation yields remainder 0, the digit is
bumped to 1.

### CNH

```php
use LumenSistemas\BrValidation\Cnh;

Cnh::isValid('98765432109');     // true
Cnh::isValid('123.456.789-00');  // true (mask characters are stripped)
Cnh::isValid('12345678901');     // false (wrong check digits)
Cnh::isValid('11111111111');     // false (all-equal sequence)

Cnh::format('123.456.789-00');   // '12345678900'
Cnh::normalize(' 12345678900 '); // '12345678900'

Cnh::generate(); // a valid 11-digit CNH
```

The CNH número de registro has no canonical visual mask
on the document, so `format()` returns the same 11-digit
raw form as `normalize()` for any 11-digit input. The
class exists primarily for `isValid()` and `generate()`.

### NF-e access key (chave de acesso)

```php
use LumenSistemas\BrValidation\Nfe;

Nfe::isValid('35240111222333000181550010000000011123456788'); // true
Nfe::isValid('3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788'); // true (masked)
Nfe::isValid('00000000000000000000000000000000000000000000');           // false (all-equal sequence)

Nfe::format('35240111222333000181550010000000011123456788');
// '3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788'

Nfe::normalize('3524 0111 2223 3300 0181 5500 1000 0000 0111 2345 6788');
// '35240111222333000181550010000000011123456788'

Nfe::generate(); // a valid 44-digit access key
```

The same 44-digit shape and check-digit algorithm cover
NF-e (modelo 55), NFC-e (modelo 65) and the broader SEFAZ
document family (CT-e, MDF-e, BP-e). This validator does
not constrain modelo.

## Behavior

- **Validation never throws.** `isValid` accepts any input
  type and returns `false` for non-string or malformed
  values. Callers can pass user input directly without
  `try/catch`.
- **All-equal sequences are rejected** (`11111111111`,
  `00000000000000`, …) even though they pass the mod-11
  algorithm. They are conventional placeholder values
  across the Brazilian validation ecosystem and never
  represent real identifiers.
- **CNPJ is case-insensitive.** Letters in alphanumeric
  CNPJs are normalized to uppercase before validation and
  formatting; both `'12abc34501de35'` and
  `'12ABC34501DE35'` validate equivalently, and `format`
  always produces the canonical uppercase masked form.
- **Numeric CNPJs remain valid in perpetuity** alongside
  the 2026 alphanumeric format. This is an accept-both
  library, not a transitional one.
- **`format()` is tolerant.** When the input shape doesn't
  match the canonical form, `format()` returns the input
  unchanged rather than raising. It does not validate
  check digits — that is `isValid()`'s job.

## Laravel integration

A companion package `lumensistemas/laravel-br-validation`
is planned, providing `Rule` classes and a service
provider. It will be linked here once published.

## Development

```bash
composer install
composer test
```

## License

MIT. See [`LICENSE`](LICENSE).
