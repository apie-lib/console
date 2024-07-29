<?php
namespace Apie\Console\Commands;

use Apie\Console\Helpers\DisplayResultHelper;
use Apie\Core\Actions\ActionResponse;
use Apie\Core\Identifiers\KebabCaseSlug;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;

final class ApieRunResourceMethodCommand extends ApieMetadataDirectedConsoleCommand
{
    protected function getCommandName(): string
    {
        assert(null !== $this->reflectionMethod);
        return KebabCaseSlug::fromClass($this->reflectionClass) . ':run:' . KebabCaseSlug::fromClass($this->reflectionMethod);
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

    protected function getSuccessMessage(ActionResponse $actionResponse): string
    {
        return 'The result was: ' . PHP_EOL . DisplayResultHelper::displayResult($actionResponse->result);
    }

    protected function requiresId(): bool
    {
        assert(null !== $this->reflectionMethod);
        return !$this->reflectionMethod->isStatic();
    }
}
