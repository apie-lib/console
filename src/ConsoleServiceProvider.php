<?php
namespace Apie\Console;

use Apie\ServiceProviderGenerator\UseGeneratedMethods;
use Illuminate\Support\ServiceProvider;

/**
 * This file is generated with apie/service-provider-generator from file: console.yaml
 * @codecoverageIgnore
 * @phpstan-ignore
 */
class ConsoleServiceProvider extends ServiceProvider
{
    use UseGeneratedMethods;

    public function register()
    {
        $this->app->singleton(
            \Apie\Console\ConsoleCommandFactory::class,
            function ($app) {
                return new \Apie\Console\ConsoleCommandFactory(
                    $app->make(\Apie\Common\ApieFacade::class)
                );
            }
        );
        $this->app->singleton(
            \Apie\ApieBundle\Wrappers\ConsoleCommandFactory::class,
            function ($app) {
                return new \Apie\ApieBundle\Wrappers\ConsoleCommandFactory(
                    $app->make(\Apie\Console\ConsoleCommandFactory::class),
                    $app->make(\Apie\Core\ContextBuilders\ContextBuilderFactory::class),
                    $app->make(\Apie\Core\BoundedContext\BoundedContextHashmap::class)
                );
            }
        );
        $this->app->bind(\Apie\ApieBundle\Wrappers\ConsoleCommandFactory::class, 'apie.console.factory');
        
        
    }
}
