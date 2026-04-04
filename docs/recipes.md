# Recipes
This document contains practical examples of how to use Scenario in a Symfony application.

---

## Create a User for Tests

### Problem
Many tests require a user to exist.

### Solution
```php
#[AsScenario('user-exists')]
#[Parameter('id', ParameterType::Integer, required: true)]
final class UserExists extends Scenario
{
    public function up(int $id): void
    {
        $user = new User($id);
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();
    }
}
```

Use in tests:
```php
#[ApplyScenario(UserExists::class, ['id' => 42])]
```

## User with Subscription

### Problem
You need a user with an active subscription.

### Solution
```php
#[AsScenario('user-with-subscription')]
#[ApplyScenario(UserExists::class)]
final class UserHasSubscription extends Scenario
{
    public function up(): void
    {
        $user = $this->repository(User::class)->findOneBy([]);

        $subscription = new Subscription($user);
        $this->entityManager()->persist($subscription);
        $this->entityManager()->flush();
    }
}
```

## Combine Multiple States

### Problem
Tests require a fully prepared system state.

### Solution
```php
#[ApplyScenario(UserExists::class, ['id' => 42])]
#[ApplyScenario(UserHasSubscription::class)]
final class SubscriptionTest extends TestCase
{
}
```

## Reset Database Before Test

### Problem
Tests interfere with each other.

### Solution
```php
#[RefreshDatabase]
final class MyTest extends TestCase
{
}
```

## Run Symfony Commands in a Scenario

## Problem
Your application already has a command for setup logic.

## Solution
```php
$this->command('app:import-users', [
    '--file' => 'var/data/users.csv',
]);
```

## Dispatch Events

### Problem
You need to trigger domain or application events.

### Solution
```php
$this->event(new UserRegisteredEvent($user));
```

## Dispatch Messenger Messages

### Problem
Your application relies on async message handling.

### Solution
```php
$this->message(new SendWelcomeEmail($user));
$this->consumer('async');
```

## Work with Files

### Problem
You need to create or manipulate files.

### Solution
```php
$file = $this->absoluteFile('var/data/users.csv', true);
$this->filesystem()->dumpFile($file, 'content');
```

## Reproduce a Bug Locally

### Problem
A bug only occurs with specific data.

## Solution
1.	Create a scenario:
```php
#[AsScenario('bug-123-state')]
final class Bug123State extends Scenario
{
    public function up(): void
    {
        // prepare exact failing state
    }
}
```
2.	Apply it:
```php
php bin/console scenario:apply bug-123-state
```
Now you can debug the issue in a reproducible environment.