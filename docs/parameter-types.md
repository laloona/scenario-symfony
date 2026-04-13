# Parameter Types
This document explains the additional parameter types provided by the Scenario Symfony adapter.

These parameter types use the Symfony Validator component and are automatically registered when the adapter is installed.

---

## Overview
The Symfony adapter extends the built-in Scenario Core parameter types with validator-based types for common input formats.

Examples include:
- email addresses
- URLs
- UUIDs
- country codes
- locale identifiers
- dates and times
- numeric constraints
- financial values

---

## Automatic Registration
All Symfony parameter types are registered automatically during bootstrap.

No manual configuration is required.

Some parameter types depend on optional PHP extensions such as `ext-intl`.

If the required dependency is not available, the type is skipped automatically.
This allows optional integrations without forcing additional dependencies.

## Generate Custom Parameter Types
You can generate a new Symfony parameter type using the CLI command:
```php
php bin/console scenario:make:parameter
```

The generated class already includes:
- `#[AsParameterType(...)]`
- Symfony validator integration
- a `constraints()` method
- a `valueType()` method

You only need to define the validation constraints and the resulting value type.

Example Generated Parameter Type:
```php
#[AsParameterType('some useful description')]
final class EmailType extends ParameterTypeDefinition
{
    protected function constraints(): array
    {
        return [
            new Email(),
        ];
    }

    protected function valueType(mixed $value): StringType
    {
        return new StringType($value);
    }
}
```

This makes creating custom Symfony-based parameter types fast and consistent.



## Available Types

#### String Validation

| Type | Description |
|------|-------------|
| EmailType | Validates email addresses |
| UrlType | Validates URLs |
| UuidType | Validates UUID values |
| IpType | Validates IPv4 and IPv6 addresses |
| HostnameType | Validates hostnames |
| IbanType | Validates IBAN values |
| BicType | Validates BIC / SWIFT codes (requires `ext-intl`) |
| IsbnType | Validates ISBN-10 and ISBN-13 values |

### Locale / Internationalization
| Type | Description |
|------|-------------|
| CountryAlpha2Type | Validates ISO 3166-1 alpha-2 country codes (requires `ext-intl`) |
| CountryAlpha3Type | Validates ISO 3166-1 alpha-3 country codes (requires `ext-intl`) |
| LanguageAlpha1Type | Validates ISO 639-1 language codes (requires `ext-intl`) |
| LanguageAlpha3Type | Validates ISO 639-2 / 639-3 language codes (requires `ext-intl`) |
| LocaleType | Validates locale identifiers such as en_US (requires 'ext-intl') |
| TimezoneType | Validates timezone identifiers (requires `ext-intl`) |

### Date and Time
| Type | Description |
|------|-------------|
| DateType | Validates dates in YYYY-MM-DD format |
| TimeType | Validates times in HH:MM:SS format |
| DateTimeType | Validates datetime values in YYYY-MM-DD HH:MM:SS format |

### Numeric Types 
| Type | Description |
|------|-------------|
| PositiveIntegerType | Validates positive integers |
| PositiveOrZeroIntegerType | Validates positive integers including zero |
| NegativeIntegerType | Validates negative integers |
| NegativeOrZeroIntegerType | Validates negative integers including zero |
| PositiveFloatType | Validates positive floating-point numbers |
| PositiveOrZeroFloatType | Validates positive floating-point numbers including zero |
| NegativeFloatType | Validates negative floating-point numbers |
| NegativeOrZeroFloatType | Validates negative floating-point numbers including zero |
| MoneyType | Validates values greater than or equal to zero with up to two decimal places |

### Example Usage:
```php
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Symfony\Parameter\EmailType;

#[Parameter('email', EmailType::class)]
```

## Checking Installed Types
Use the CLI command to verify which Symfony parameter types were loaded:

```bash
php vendor/bin/scenario parameter
```
This is especially useful when optional extensions such as `ext-intl are missing.

## Why Use Symfony Types?
Symfony parameter types allow you to reuse the mature Symfony Validator ecosystem inside Scenario definitions.

Benefits:
- reliable validation
- well-tested constraints
- no custom regex needed
- clear intent in scenario definitions

## Best Practices
- Use Symfony types for common formats such as email, UUID, locale, or money
- Prefer semantic types over generic String
- Use numeric types to validate scenario input early
- Check the CLI listing when a type is unexpectedly unavailable

---

## Next Steps
- [CLI Usage](cli.md)
- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)
