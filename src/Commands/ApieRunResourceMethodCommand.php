<?php
namespace Apie\Console\Commands;

use Apie\Core\Actions\ActionResponse;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;

final class ApieRunResourceMethodCommand extends ApieMetadataDirectedConsoleCommand
{
    protected function getCommandName(): string
    {
        assert(null !== $this->reflectionMethod);
        return 'run-' . $this->reflectionClass->getShortName() . '-' . $this->reflectionMethod->name;
    }

    protected function getCommandHelp(): string
    {
        assert(null !== $this->reflectionMethod);
        return 'This command runs ' . $this->reflectionMethod->getName() .  ' from a ' . $this->reflectionClass->getShortName() .  ' instance';
    }

    protected function getMetadata(): MetadataInterface
    {
        assert(null !== $this->reflectionMethod);
        return MetadataFactory::getMethodMetadata(
            $this->reflectionMethod,
            $this->apieContext
        );
    }

    protected function getSucessMessage(ActionResponse $actionResponse): string
    {
        return 'TODO';
    }

    protected function requiresId(): bool
    {
        assert(null !== $this->reflectionMethod);
        return !$this->reflectionMethod->isStatic();
    }
}
