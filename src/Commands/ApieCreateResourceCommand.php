<?php
namespace Apie\Console\Commands;

use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;

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

    protected function getSucessMessage(): string
    {
        return "Resource was successfully created.";
    }

    protected function requiresId(): bool
    {
        return false;
    }
}
