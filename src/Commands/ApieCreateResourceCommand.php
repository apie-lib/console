<?php
namespace Apie\Console\Commands;

use Apie\Core\Actions\ActionResponse;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;
use ReflectionClass;

final class ApieCreateResourceCommand extends ApieMetadataDirectedConsoleCommand
{
    protected function getCommandName(): string
    {
        return 'create-' . $this->reflectionClass->getShortName();
    }

    protected function getCommandHelp(): string
    {
        return 'This command allows you to create a ' . $this->reflectionClass->getShortName() .  ' instance';
    }

    protected function getMetadata(): MetadataInterface
    {
        return MetadataFactory::getCreationMetadata(
            $this->reflectionClass,
            $this->apieContext
        );
    }

    protected function getSucessMessage(ActionResponse $actionResponse): string
    {
        return sprintf(
            "Resource %s with id %s was successfully created.",
            (new ReflectionClass($actionResponse->resource))->getShortName(),
            $actionResponse->resource->getId(),
        );
    }

    protected function requiresId(): bool
    {
        return false;
    }
}
