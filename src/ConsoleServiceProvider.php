<?php
namespace Apie\Console;

use Apie\ServiceProviderGenerator\UseGeneratedMethods;
use Illuminate\Support\ServiceProvider;

/**
 * This file is generated with apie/service-provider-generator from file: console.yaml
 * @codecoverageIgnore
 */
class ConsoleServiceProvider extends ServiceProvider
{
    use UseGeneratedMethods;

    public function register()
    {
        $this->app->singleton(
            \Apie\Common\Wrappers\ConsoleCommandFactory::class,
            function ($app) {
                return new \Apie\Common\Wrappers\ConsoleCommandFactory(
                    $app->make(\Apie\Console\ConsoleCommandFactory::class),
                    $app->make(\Apie\Core\ContextBuilders\ContextBuilderFactory::class),
                    $app->make(\Apie\Core\BoundedContext\BoundedContextHashmap::class)
                );
            }
        );
        $this->app->bind('apie.console.factory', \Apie\Common\Wrappers\ConsoleCommandFactory::class);
        
        $this->app->singleton(
            \Apie\Console\ConsoleCommandFactory::class,
            function ($app) {
                return new \Apie\Console\ConsoleCommandFactory(
                    $app->make(\Apie\Common\ApieFacade::class),
                    $app->make(\Apie\Common\ActionDefinitionProvider::class),
                    $app->make(\Apie\Console\ApieInputHelper::class)
                );
            }
        );
        $this->app->singleton(
            \Apie\Console\ApieInputHelper::class,
            function ($app) {
                return \Apie\Console\ApieInputHelper::create(
                    $this->getTaggedServicesIterator(\Apie\Console\Helpers\InputInteractorInterface::class)
                );
                
            }
        );
        \Apie\ServiceProviderGenerator\TagMap::register(
            $this->app,
            \Apie\Console\ApieInputHelper::class,
            array(
              0 =>
              array(
                'name' => 'console.helper',
              ),
            )
        );
        $this->app->tag([\Apie\Console\ApieInputHelper::class], 'console.helper');
        
    }
}
