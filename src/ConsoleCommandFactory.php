<?php
namespace Apie\Console;

use Apie\Common\Actions\CreateObjectAction;
use Apie\Common\ApieFacade;
use Apie\Console\Commands\ApieConsoleCommand;
use Apie\Console\Lists\ConsoleCommandList;
use Apie\Core\BoundedContext\BoundedContext;
use Apie\Core\Context\ApieContext;
use Apie\Core\Enums\ConsoleCommand;
use Apie\Core\Enums\RequestMethod;

class ConsoleCommandFactory
{
    public function __construct(private readonly ApieFacade $apieFacade)
    {
    }

    public function createForBoundedContext(BoundedContext $boundedContext, ApieContext $apieContext): ConsoleCommandList
    {
        $commands = [];

        $postContext = $apieContext->withContext(RequestMethod::class, RequestMethod::POST)
            ->withContext(ConsoleCommand::class, ConsoleCommand::CONSOLE_COMMAND)
            ->withContext(ConsoleCommand::CONSOLE_COMMAND->value, true)
            ->registerInstance($boundedContext);

        $action = new CreateObjectAction($this->apieFacade);
        foreach ($boundedContext->resources->filterOnApieContext($postContext) as $resource) {
            $commands = new ApieConsoleCommand($action, $postContext, $resource);
        }
        return new ConsoleCommandList($commands);
    }
}
