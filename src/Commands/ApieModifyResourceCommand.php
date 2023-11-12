<?php
namespace Apie\Console\Commands;

use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;

final class ApieModifyResourceCommand extends ApieMetadataDirectedConsoleCommand
{
    protected function getCommandName(): string
    {
        return 'modify-' . $this->reflectionClass->getShortName();
    }

    protected function getCommandHelp(): string
    {
        return 'This command allows you to modify a ' . $this->reflectionClass->getShortName() .  ' instance';
    }

    protected function getMetadata(): MetadataInterface
    {
        return MetadataFactory::getModificationMetadata(
            $this->reflectionClass,
            $this->apieContext
        );
    }

    protected function getSucessMessage(): string
    {
        return "Resource was successfully modified.";
    }

    protected function requiresId(): bool
    {
        return true;
    }
}
