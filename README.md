# Scenario Symfony

Symfony integration for Stateforge Scenario Core.

This package provides framework-specific integration for Symfony applications,
enabling seamless scenario execution within PHPUnit tests and console workflows.

It builds on top of ``stateforge/scenario-core`` and integrates with the Symfony runtime
and Doctrine ORM.

## Requirements
Scenario Symfony requires the following:

* PHP >= 8.2.
* Symfony 6.4+ or 7+
* [stateforge/scenario-core](https://github.com/laloona/scenario-core)

## Installation

> This package is intended for test and development use only.

<pre><code>
composer require --dev stateforge/scenario-symfony
</code></pre>

After installation, run the setup command:

<pre><code>
php bin/console scenario:install
</code></pre>

The installation command generates the required configuration files:
* creates the ``scenario.yaml`` package configuration
* enables the symfony console scenario commands
* generates the ``scenario.dist.xml``for configuration
* places the extendsion into ``phpunit.dist.xml`` or ``phpunit.xml``

## What This Package Provides

Scenario Symfony integrates Scenario Core with:
* Symfony’s service container
* Doctrine ORM (for database reset handling)
* Symfony Console
* Symfony test kernel lifecycle

## Enabling the Bundle

Register the bundle in your Symfony application:
<pre><code type="php">&lt;?php
// config/bundles.php

return [
    Stateforge\Scenario\Symfony\ScenarioSymfonyBundle::class => ['dev' => true, 'test' => true],
];
</code></pre>

The bundle automatically:
* registers attribute handlers
* wires scenario services
* configures database reset handling
* integrates with PHPUnit extension

## Database Reset (Doctrine Integration)

When using ``#[RefreshDatabase]``, the Symfony integration resets the database using Doctrine.

The default behavior:
* recreate the database
* executed all migrations

## Applying Scenarios in Unit Tests

Scenarios can be applied declaratively using the ```#[ApplyScenario]``` attribute:

<pre><code type="php">&lt;?php
use Stateforge\Scenario\Core\Attribute\ApplyScenario;

#[ApplyScenario('my-scenario')]
final class MyTest extends TestCase
{
    #[ApplyScenario('my-second-scenario')]
    public function testSomethingImportant(): void
    {
        // scenario has already been applied, data can be tested
    }
}
</code></pre>

## Console Commands

Scenario Symfony registers dedicated console commands within your Symfony application.

You can discover them using:
<pre><code>
php bin/console list scenario
</code></pre>