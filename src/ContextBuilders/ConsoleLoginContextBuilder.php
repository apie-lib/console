<?php
namespace Apie\Console\ContextBuilders;

use Apie\Common\Interfaces\CheckLoginStatusInterface;
use Apie\Common\ValueObjects\DecryptedAuthenticatedUser;
use Apie\Common\Wrappers\TextEncrypter;
use Apie\Console\ConsoleCliStorage;
use Apie\Core\Context\ApieContext;
use Apie\Core\ContextBuilders\ContextBuilderInterface;
use Apie\Core\ContextConstants;
use Apie\Core\Datalayers\ApieDatalayer;
use Apie\Core\Exceptions\EntityNotFoundException;
use Apie\Core\ValueObjects\Exceptions\InvalidStringForValueObjectException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Psr\Http\Message\ServerRequestInterface;

class ConsoleLoginContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly ConsoleCliStorage $consoleCliStorage,
    ) {
    }

    public function process(ApieContext $context): ApieContext
    {
        $textEncrypter = $context->getContext(TextEncrypter::class, false);
        $apieDatalayer = $context->getContext(ApieDatalayer::class, false);
        $request = $context->getContext(ServerRequestInterface::class, false);
        if ($request || !$textEncrypter || !$apieDatalayer) {
            // Storage only available in apie/console
            return $context;
        }
        $cliStorage = $this->consoleCliStorage;
        $authenticated = $cliStorage->restore('_APIE_AUTHENTICATED');
        if ($authenticated === null) {
            return $context;
        }
        try {
            $decrypted = DecryptedAuthenticatedUser::fromNative($textEncrypter->decrypt($authenticated));
            if ($decrypted->isExpired()) {
                $cliStorage->remove('_APIE_AUTHENTICATED');
                return $context;
            }
            $entity = $apieDatalayer->find($decrypted->getId(), $decrypted->getBoundedContextId());
            if ($entity instanceof CheckLoginStatusInterface && $entity->isDisabled()) {
                $cliStorage->remove('_APIE_AUTHENTICATED');
                return $context;
            }
            return $context->withContext(ContextConstants::AUTHENTICATED_USER, $entity)
                ->withContext(DecryptedAuthenticatedUser::class, $decrypted);
        } catch (WrongKeyOrModifiedCiphertextException) {
            // key is not removed, because it might be a valid key in another project
            return $context;
        } catch (InvalidStringForValueObjectException|EntityNotFoundException) {
            $cliStorage->remove('_APIE_AUTHENTICATED');
            return $context;
        }
    }
}
