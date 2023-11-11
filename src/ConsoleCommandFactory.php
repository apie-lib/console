<?php
namespace Apie\Console;

use Apie\Common\ActionDefinitionProvider;
use Apie\Common\ActionDefinitions\CreateResourceActionDefinition;
use Apie\Common\ActionDefinitions\ReplaceResourceActionDefinition;
use Apie\Common\Actions\CreateObjectAction;
use Apie\Common\ApieFacade;
use Apie\Common\ContextConstants;
use Apie\Console\Commands\ApieConsoleCommand;
use Apie\Console\Lists\ConsoleCommandList;
use Apie\Core\BoundedContext\BoundedContext;
use Apie\Core\Context\ApieContext;
use Apie\Core\Enums\ConsoleCommand;

class ConsoleCommandFactory
{
    public function __construct(
        private readonly ApieFacade $apieFacade,
        private readonly ActionDefinitionProvider $actionDefinitionProvider,
        private readonly ApieInputHelper $apieInputHelper
    ) {
    }

    public function createForBoundedContext(BoundedContext $boundedContext, ApieContext $apieContext): ConsoleCommandList
    {
        $commands = [];
        $apieContext = $apieContext->withContext(ConsoleCommand::class, ConsoleCommand::CONSOLE_COMMAND)
            ->withContext(ConsoleCommand::CONSOLE_COMMAND->value, true)
            ->withContext(ContextConstants::BOUNDED_CONTEXT_ID, $boundedContext->getId())
            ->registerInstance($boundedContext);
        foreach ($this->actionDefinitionProvider->provideActionDefinitions($boundedContext, $apieContext) as $actionDefinition) {
            $action = null;
            $resourceName = null;
            if ($actionDefinition instanceof CreateResourceActionDefinition || $actionDefinition instanceof ReplaceResourceActionDefinition) {
                $action = new CreateObjectAction($this->apieFacade);
                $resourceName = $actionDefinition->getResourceName();
            }
            if ($action !== null) {
                $commands[] = new ApieConsoleCommand($action, $apieContext, $resourceName, $this->apieInputHelper);
            }
        }

        return new ConsoleCommandList($commands);
    }
}
