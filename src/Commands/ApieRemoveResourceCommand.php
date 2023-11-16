<?php
namespace Apie\Console\Commands;

use Apie\Core\Actions\ActionResponse;
use Apie\Core\Identifiers\KebabCaseSlug;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;
use Apie\TypeConverter\ReflectionTypeFactory;

final class ApieRemoveResourceCommand extends ApieMetadataDirectedConsoleCommand
{
    protected function getCommandName(): string
    {
        return KebabCaseSlug::fromClass($this->reflectionClass) . ':remove';
    }

    protected function getCommandHelp(): string
    {
        return 'This command allows you to remove a ' . $this->reflectionClass->getShortName() .  ' instance';
    }

    protected function getMetadata(): MetadataInterface
    {
        return MetadataFactory::getMetadataStrategyForType(ReflectionTypeFactory::createReflectionType('null'))
            ->getCreationMetadata($this->apieContext);
    }

    protected function getSucessMessage(ActionResponse $actionResponse): string
    {
        return "Resource was successfully deleted.";
    }

    protected function requiresId(): bool
    {
        return true;
    }
}
