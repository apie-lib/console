<img src="https://raw.githubusercontent.com/apie-lib/apie-lib-monorepo/main/docs/apie-logo.svg" width="100px" align="left" />
<h1>console</h1>






 [![Latest Stable Version](https://poser.pugx.org/apie/console/v)](https://packagist.org/packages/apie/console) [![Total Downloads](https://poser.pugx.org/apie/console/downloads)](https://packagist.org/packages/apie/console) [![Latest Unstable Version](https://poser.pugx.org/apie/console/v/unstable)](https://packagist.org/packages/apie/console) [![License](https://poser.pugx.org/apie/console/license)](https://packagist.org/packages/apie/console) [![PHP Composer](https://apie-lib.github.io/projectCoverage/coverage-console.svg)](https://apie-lib.github.io/projectCoverage/console/index.html)  

[![PHP Composer](https://github.com/apie-lib/console/actions/workflows/php.yml/badge.svg?event=push)](https://github.com/apie-lib/console/actions/workflows/php.yml)

This package is part of the [Apie](https://github.com/apie-lib) library.
The code is maintained in a monorepo, so PR's need to be sent to the [monorepo](https://github.com/apie-lib/apie-lib-monorepo/pulls)

## Documentation
Runs Apie actions (defined in [apie/common](https://packagist.org/packages/apie/common)) as interactive Symfony Console commands, prompting for missing arguments and rendering the results on the CLI.

### Standalone usage
Install it with:
```bash
composer require apie/console
```

Construct `Apie\Console\ConsoleCommandFactory` with an `ApieFacade`, an `ActionDefinitionProvider`,
an `Apie\Console\ApieInputHelper` and an `Apie\Console\ConsoleCliStorage` to turn Apie actions
into `Symfony\Component\Console\Command\Command` instances. `ApieInputHelper` collects
`Apie\Console\Helpers\InputInteractorInterface` implementations to interactively ask the user
for each property of an action's input.

### Symfony integration
Via `apie/apie-bundle`, `console.yaml` registers `Apie\Console\ConsoleCommandFactory` as
`apie.console.factory` and adds a `ConsoleLoginContextBuilder` so console commands run in an
authenticated context. The generated commands are then available as regular `bin/console` commands.

### Laravel integration
Via `apie/laravel-apie`, the generated `Apie\Console\ConsoleServiceProvider` registers the same
`ConsoleCommandFactory` and context builder so the Apie actions are exposed as Artisan commands.
