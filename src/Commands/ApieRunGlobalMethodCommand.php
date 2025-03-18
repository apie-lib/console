<?php
namespace Apie\Console\Commands;

use Apie\Common\ValueObjects\DecryptedAuthenticatedUser;
use Apie\Common\Wrappers\TextEncrypter;
use Apie\Console\Helpers\DisplayResultHelper;
use Apie\Core\Actions\ActionResponse;
use Apie\Core\BoundedContext\BoundedContextId;
use Apie\Core\ContextConstants;
use Apie\Core\Entities\EntityInterface;
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
        if ($this->reflectionMethod->name === 'verifyAuthentication'
            && $actionResponse->result instanceof EntityInterface
            && $actionResponse->apieContext->hasContext(TextEncrypter::class)) {
            /** @var TextEncrypter $textEncrypter */
            $textEncrypter = $actionResponse->apieContext->getContext(TextEncrypter::class);
            $decryptedUserId = DecryptedAuthenticatedUser::createFromEntity(
                $actionResponse->result,
                new BoundedContextId($actionResponse->apieContext->getContext(ContextConstants::BOUNDED_CONTEXT_ID)),
                time() + 3600
            );
            $this->consoleCliStorage->store('_APIE_AUTHENTICATED', $textEncrypter->encrypt($decryptedUserId->toNative()));
        }
        return 'The result was: ' . PHP_EOL . DisplayResultHelper::displayResult($actionResponse->result);
    }

    protected function requiresId(): bool
    {
        return false;
    }
}
