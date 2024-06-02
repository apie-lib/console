<?php
namespace Apie\Console\Commands;

use Apie\Core\Actions\ActionResponse;
use Apie\Core\Identifiers\KebabCaseSlug;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;

final class ApieRunGlobalMethodCommand extends ApieMetadataDirectedConsoleCommand
{
    protected function getCommandName(): string
    {
        assert(null !== $this->reflectionMethod);
        return KebabCaseSlug::fromClass($this->reflectionClass) . ':run:' . KebabCaseSlug::fromClass($this->reflectionMethod);
    }

    protected function getCommandHelp(): string
    {
        assert(null !== $this->reflectionMethod);
        return 'This command runs ' . $this->reflectionMethod->getName() .  ' from service ' . $this->reflectionClass->getShortName();
    }

    protected function getMetadata(): MetadataInterface
    {
        assert(null !== $this->reflectionMethod);
        return MetadataFactory::getMethodMetadata(
            $this->reflectionMethod,
            $this->apieContext
        );
    }

    protected function getSuccessMessage(ActionResponse $actionResponse): string
    {
        return 'TODO';
    }

    protected function requiresId(): bool
    {
        return false;
    }
}
