# Configuration
This document explains the Scenario configuration used by the Scenario Symfony adapter.

The Symfony adapter uses the shared Scenario XML configuration format from Scenario Core, but provides Symfony-specific installation and database integration.

---

## Overview
Scenario Symfony uses the same XML configuration format as Scenario Core.

The configuration defines how Scenario discovers scenarios, parameter types, bootstrap files, cache directories, and optional database integrations.

The Symfony adapter adds Symfony-specific defaults during installation and bootstrap.

---

## Creating the Configuration
The Symfony adapter provides an install command that generates the configuration automatically:

```bash
php bin/console scenario:install
```
This command creates the XML configuration file in the project root directory and also generates the required Symfony bootstrap file.

## Default File Location
The default configuration file name is:
```text
scenario.dist.xml
```
located in the project root directory.

Projects may copy this file to `scenario.xml` for local customization.

## Example Configuration
```xml
<scenario xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="vendor/stateforge/scenario-core/xsd/scenario.xsd"
          cacheDirectory=".scenario.cache"
          parameterDirectory="scenario/parameter"
>
    <database>
        <connection name="default">migrations/main.php</connection>
    </database>

    <suites>
        <suite name="Main Scenario Suite">
            <directory>scenario/main</directory>
        </suite>
    </suites>

    <extensions>
        <bootstrap class="Stateforge\Scenario\Symfony\Extension"/>
    </extensions>
</scenario>
```

### Root Element
```xml
<scenario>
```
This is the root element of the configuration.

#### Attributes
##### cacheDirectory
```xml
cacheDirectory=".scenario.cache"
```
Directory used for generated cache files.

Scenario caches discovered scenarios and parameter types for faster startup.

##### parameterDirectory
```xml
parameterDirectory="scenario/parameter"
```
Directory containing custom parameter type classes.

All compatible parameter types in this directory are discovered automatically.

### Extensions
The `<extensions>` element registers framework adapters and custom initialization logic.
Place it after `<suites>` and keep the Symfony adapter registered:

```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Symfony\Extension" />
</extensions>
```

The adapter extension connects Scenario Core to Symfony's application bootstrap and
registers the framework-specific scenario and database refresh handlers.

Each `<bootstrap>` element requires a `class` attribute naming an autoloadable class
that implements `Stateforge\Scenario\Core\Extension`. Scenario instantiates the class
without constructor arguments. The element has no text content.

You can add custom extensions alongside the adapter:

```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Symfony\Extension" />
    <bootstrap class="App\Scenario\Extension" />
</extensions>
```

For custom extensions, `application()` returns an
`Stateforge\Scenario\Core\Runtime\ApplicationExtension` or `null`.
Its `prepare(Application $application)` hook runs before parameter types and scenarios
are loaded; its `boot(Application $application)` hook runs during Scenario application bootstrap.

The `cli()` method returns a `Stateforge\Scenario\Core\Console\CliApplicationExtension`
or `null`. Its `boot(CliApplication $cli)` hook can register commands for Scenario Core's
`vendor/bin/scenario` CLI. Framework console commands are registered through Symfony's
own command integration.

### Suites
Suites organize scenario classes into logical groups.

Example:
```xml
<suites>
    <suite name="main" description="Main Scenario Suite">
        <directory>scenario/main</directory>
    </suite>
    <suite name="cli" description="Cli for Tests">
        <directory>scenario/cli</directory>
    </suite>
</suites>
```

#### Suite Attributes
##### name
Short human-readable suite name.
Used in CLI output and interactive commands.

##### description
Human-readable suite name.
Used in CLI output.

#### directory
Directory containing scenario classes.

All scenarios inside this directory are discovered automatically.

### Multiple Suites
You may define multiple suites.

This is useful for separating:
- test scenarios
- development scenarios
- fixtures
- domain-specific states

### Database Configuration
Optional database connections may be configured.
```xml
<database>
    <connection name="default">migrations/main.php</connection>
</database>
```
In the Symfony adapter, the `name` attribute is interpreted as the value passed to Doctrine's `--connection` option and the value of the `<connection>` element is interpreted as the value passed to Doctrine’s `--configuration` option.

This allows Scenario Symfony to delegate database migration setup to Doctrine using your project-specific migration configuration.

#### Example:
```xml
<database>
    <connection name="default">migrations/main.php</connection>
</database>
```

This will be used like:
```bash
php bin/console doctrine:migrations:migrate --connection=default --configuration=migrations/main.php
```

#### Why This Matters
Scenario Symfony does not require you to implement database migration logic manually.

Instead, it integrates with Doctrine and uses the configured migration file to prepare the database.

This keeps the database refresh process aligned with normal Symfony and Doctrine project conventions.

#### Multiple Connections
You may define multiple named connections if your project uses more than one migration setup.

Example:
```xml
<database>
    <connection name="default">migrations/main.php</connection>
    <connection name="reporting">migrations/reporting.php</connection>
</database>
```

## Schema Validation
The configuration file is validated against the bundled XML schema:
```xml
vendor/stateforge/scenario-core/xsd/scenario.xsd
```
Invalid configuration values will fail early during bootstrap.

## Best Practices
- keep the file in project root
- use descriptive suite names
- separate scenarios into multiple suites when needed
- keep the generated Symfony bootstrap file
- use Doctrine migration configuration files that match your normal project setup
- commit the configuration file to version control

---

## Next Steps
- [Scenarios](scenarios.md)
- [Parameter Types](parameter-types.md)
- [CLI Usage](cli.md)
- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)
