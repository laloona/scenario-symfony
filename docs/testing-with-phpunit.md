# Testing with PHPUnit

This document explains how Scenario integrates with PHPUnit in a Symfony application.

---

## Why use Scenario in tests?
Traditional test setup often involves:
- manual fixture creation
- duplicated setup logic
- hard-to-maintain test data

Scenario replaces this with **declarative state definition**.

Instead of writing setup code, you describe the desired state:

```php
#[ApplyScenario(UserExists::class)]
```

## Enabling PHPUnit Integration
Make sure the Scenario PHPUnit extension is registered:
```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Core\PHPUnit\Extension" />
</extensions>
```

Alternatively, the CLI install command can configure PHPUnit automatically.

In Symfony projects, this is typically done via:
```bash
php bin/console scenario:install
```

This ensures that all scenario attributes are processed before test execution.

## Symfony Test Environment
Scenario integrates with the Symfony test kernel:
- services are available via the container
- Doctrine is fully usable
- Messenger and events are available

This allows scenarios to interact with real application services.

## Applying Scenarios

### At class level
```php
use Stateforge\Scenario\Core\Attribute\ApplyScenario;

#[ApplyScenario(UserExists::class)]
final class MyTest extends TestCase
{
}
```
The scenario is applied before each test method.

### At method level
```php
#[ApplyScenario(UserExists::class)]
public function testSomething(): void
{
}
```

### Combining both
```php
#[ApplyScenario(UserExists::class)]
final class MyTest extends TestCase
{
    #[ApplyScenario(UserHasSubscription::class)]
    public function testAccess(): void
    {
    }
}
```
Execution order:
1. class-level scenarios
2. method-level scenarios

## Passing Parameters
```php
#[ApplyScenario(CreateUserScenario::class, ['email' => 'test@example.com'])]
```
Parameters are validated before execution.

## Resetting State
Use `#[RefreshDatabase]` to ensure a clean environment:

```php
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

#[RefreshDatabase]
final class MyTest extends TestCase
{
}
```
In Symfony, the database reset is handled via Doctrine:
- database is recreated
- migrations are executed

## Scenario Composition in Tests
You can compose complex states:
```php
#[ApplyScenario(UserExists::class, ['id' => 42])]
#[ApplyScenario(UserHasSubscription::class, ['id' => 42])]
final class SubscriptionTest extends TestCase
{
}
```
This keeps tests:
- readable
- reusable
- focused

## Error Handling
If a scenario fails:
- the test fails immediately
- the exception is wrapped and reported via PHPUnit

__Example:__
- application failure → `ApplicationFailureException`
- class-level failure → `TestClassFailureException`
- method-level failure → `TestMethodFailureException`

## Summary
Scenario is not a replacement for:
- unit-level object testing
- pure domain logic tests

Use it when:
- state preparation is complex
- integration behavior is tested
- data dependencies exist

---

## Next Steps

- [Recipes](recipes.md)