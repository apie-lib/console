<?php
namespace Apie\Console;

use Apie\Common\ActionDefinitionProvider;
use Apie\Common\ActionDefinitions\CreateResourceActionDefinition;
use Apie\Common\ActionDefinitions\ModifyResourceActionDefinition;
use Apie\Common\ActionDefinitions\RemoveResourceActionDefinition;
use Apie\Common\ActionDefinitions\ReplaceResourceActionDefinition;
use Apie\Common\ActionDefinitions\RunGlobalMethodDefinition;
use Apie\Common\ActionDefinitions\RunResourceMethodDefinition;
use Apie\Common\Actions\CreateObjectAction;
use Apie\Common\Actions\ModifyObjectAction;
use Apie\Common\Actions\RemoveObjectAction;
use Apie\Common\Actions\RunAction;
use Apie\Common\Actions\RunItemMethodAction;
use Apie\Common\ApieFacade;
use Apie\Common\ContextConstants;
use Apie\Console\Commands\ApieCreateResourceCommand;
use Apie\Console\Commands\ApieModifyResourceCommand;
use Apie\Console\Commands\ApieRemoveResourceCommand;
use Apie\Console\Commands\ApieRunGlobalMethodCommand;
use Apie\Console\Commands\ApieRunResourceMethodCommand;
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
            $className = null;
            $method = null;
            // create
            if ($actionDefinition instanceof CreateResourceActionDefinition || $actionDefinition instanceof ReplaceResourceActionDefinition) {
                $action = new CreateObjectAction($this->apieFacade);
                $resourceName = $actionDefinition->getResourceName();
                $className = ApieCreateResourceCommand::class;
            }
            if ($actionDefinition instanceof RemoveResourceActionDefinition) {
                $action = new RemoveObjectAction($this->apieFacade);
                $resourceName = $actionDefinition->getResourceName();
                $className = ApieRemoveResourceCommand::class;
            }
            if ($actionDefinition instanceof ModifyResourceActionDefinition) {
                $action = new ModifyObjectAction($this->apieFacade);
                $resourceName = $actionDefinition->getResourceName();
                $className = ApieModifyResourceCommand::class;
            }
            if ($actionDefinition instanceof RunResourceMethodDefinition) {
                $action = new RunItemMethodAction($this->apieFacade);
                $resourceName = $actionDefinition->getResourceName();
                $className = ApieRunResourceMethodCommand::class;
                $method = $actionDefinition->getMethod();
            }
            if ($actionDefinition instanceof RunGlobalMethodDefinition) {
                $action = new RunAction($this->apieFacade);
                $method = $actionDefinition->getMethod();
                $resourceName = $method->getDeclaringClass();
                $className = ApieRunGlobalMethodCommand::class;
            }
            if ($action !== null && $className !== null) {
                $commands[] = new $className($action, $apieContext, $resourceName, $this->apieInputHelper, $method);
            }
        }
        return new ConsoleCommandList($commands);
    }
}
