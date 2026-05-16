# br-validation

[![Tests](https://github.com/lumensistemas/br-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/lumensistemas/br-validation/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/lumensistemas/br-validation.svg)](https://packagist.org/packages/lumensistemas/br-validation)
[![Total Downloads](https://img.shields.io/packagist/dt/lumensistemas/br-validation.svg)](https://packagist.org/packages/lumensistemas/br-validation)

Validators, generators, and formatters for Brazilian
identifiers (CPF, CNPJ, PIS, Título de Eleitor, CNH,
Renavam, placa de veículo, CNS, chave PIX, CEP, and NF-e
access key) in PHP. Framework-agnostic and dependency-free
at runtime.

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

### Renavam

```php
use LumenSistemas\BrValidation\Renavam;

Renavam::isValid('01234567897');     // true
Renavam::isValid('98765432103');     // true
Renavam::isValid('00000000000');     // false (all-equal sequence)
Renavam::isValid(1234567897);        // false (non-string input)

Renavam::format('01234567897');      // '01234567897'
Renavam::normalize(' 01234567897 '); // '01234567897'

Renavam::generate(); // a valid 11-digit Renavam
```

Like CNH, Renavam has no canonical visual mask, so
`format()` returns the same 11-digit raw form as
`normalize()`. Pre-2007 nine-digit Renavams must be
left-padded with zeros by the caller before validation.

### Placa de veículo

```php
use LumenSistemas\BrValidation\Placa;

Placa::isValid('ABC-1234'); // true (old format)
Placa::isValid('abc1234');  // true (case-insensitive)
Placa::isValid('ABC1D23');  // true (Mercosul format)
Placa::isValid('ABCD123');  // false (wrong shape)

Placa::format('ABC1234');   // 'ABC-1234'
Placa::format('abc1d23');   // 'ABC1D23'
Placa::normalize(' abc-1234 '); // 'ABC1234'

Placa::generateOld();       // a valid old-format placa
Placa::generateMercosul();  // a valid Mercosul placa
```

Old and Mercosul plates coexist on the road indefinitely
(vehicles only switch to Mercosul on first registration
or transfer), so this is an accept-both library, not a
transitional one. Placas carry no check digit; only the
shape is validated.

### CNS (Cartão Nacional de Saúde)

```php
use LumenSistemas\BrValidation\Cns;

Cns::isValid('120 6532 8705 0007'); // true (definitive)
Cns::isValid('900000000000008');    // true (provisional, starts with 9)
Cns::isValid('320653287050007');    // false (first digit must be 1, 2, 7, 8, or 9)
Cns::isValid('100000000060026');    // false (definitive with non-000/001 appendix)

Cns::format('120653287050007');     // '120 6532 8705 0007'
Cns::normalize('120 6532 8705 0007'); // '120653287050007'

Cns::generateDefinitive();   // a valid definitive CNS (first digit 1 or 2)
Cns::generateProvisional();  // a valid provisional CNS (first digit 7, 8, or 9)
```

Two structural shapes share one mod-11 check: definitive
cards (first digit 1 or 2) carry the citizen's PIS in
positions 1–11 and a `000`/`001` appendix in positions
12–14; provisional cards (first digit 7, 8, or 9) carry
no internal structure. The validator enforces the
appendix pattern for definitive cards so that
arithmetically-conforming numbers the SUS would never
issue are still rejected.

### Chave PIX

```php
use LumenSistemas\BrValidation\Pix;

Pix::isValid('85698104077');                              // true (CPF key)
Pix::isValid('user@example.com');                         // true (email key)
Pix::isValid('+5511987654321');                           // true (phone key)
Pix::isValid('123e4567-e89b-42d3-a456-426614174000');     // true (EVP key)
Pix::isValid('11.222.333/0001-81');                       // true (CNPJ key)
Pix::isValid('not-a-key');                                // false

Pix::type('user@example.com');                            // 'email'
Pix::type('85698104077');                                 // 'cpf'

Pix::normalize('Test.User@Example.COM');                  // 'test.user@example.com'
Pix::normalize('856.981.040-77');                         // '85698104077'

Pix::generateEvp(); // a valid UUID v4 EVP key
```

`Pix` is a thin dispatcher over the five chave PIX shapes
(CPF, CNPJ, e-mail, E.164 phone starting with `+55`, and
UUID v4 EVP); CPF and CNPJ delegate to the existing
validators. No `format()` method is exposed because the
canonical display form depends on the type — use the
type-specific `Cpf::format()` / `Cnpj::format()` when
you need user-facing display.

### CEP

```php
use LumenSistemas\BrValidation\Cep;

Cep::isValid('01310-100');     // true
Cep::isValid('01310100');      // true (raw form also accepted)
Cep::isValid('0131010');       // false (too short)

Cep::format('01310100');       // '01310-100'
Cep::normalize('01310-100');   // '01310100'

Cep::generate(); // a shape-valid 8-digit CEP (not guaranteed to exist)
```

CEP has no check digit — this is shape validation only.
Whether a given CEP corresponds to a real address
requires a Correios lookup and is out of scope. All-equal
sequences like `00000000` therefore pass; callers that
need existence checks should integrate a lookup service
separately.

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
