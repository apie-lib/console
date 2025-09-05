<?php
namespace Apie\Tests\Console\ContextBuilders;

use Apie\Common\Interfaces\CheckLoginStatusInterface;
use Apie\Common\ValueObjects\DecryptedAuthenticatedUser;
use Apie\Common\Wrappers\TextEncrypter;
use Apie\Console\ConsoleCliStorage;
use Apie\Console\ContextBuilders\ConsoleLoginContextBuilder;
use Apie\Core\BoundedContext\BoundedContextId;
use Apie\Core\Context\ApieContext;
use Apie\Core\ContextConstants;
use Apie\Core\Datalayers\ApieDatalayer;
use Apie\Core\Exceptions\EntityNotFoundException;
use Apie\Core\Other\MockFileWriter;
use Apie\Core\ValueObjects\DatabaseText;
use Apie\Fixtures\Entities\UserWithAddress;
use Apie\Fixtures\Identifiers\UserWithAddressIdentifier;
use Apie\Fixtures\ValueObjects\AddressWithZipcodeCheck;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ConsoleLoginContextBuilderTest extends TestCase
{
    private ConsoleCliStorage $cliStorage;
    private MockFileWriter $mockFileWriter;
    private $originalRootPath;
    private $originalHomePath;

    protected function setUp(): void
    {
        $this->mockFileWriter = new MockFileWriter();
        $this->cliStorage = new ConsoleCliStorage($this->mockFileWriter);
        $ref = new ReflectionClass($this->cliStorage);
        $rootPathProp = $ref->getProperty('rootPath');
        $rootPathProp->setAccessible(true);
        $this->originalRootPath = $rootPathProp->getValue($this->cliStorage);
        $rootPathProp->setValue($this->cliStorage, '/tmp/root');
        $homePathProp = $ref->getProperty('homePath');
        $homePathProp->setAccessible(true);
        $this->originalHomePath = $homePathProp->getValue($this->cliStorage);
        $homePathProp->setValue($this->cliStorage, '/tmp/home');
    }

    #[Test]
    public function do_nothing_when_unaffected()
    {
        $builder = new ConsoleLoginContextBuilder($this->cliStorage);
        $context = new ApieContext();
        $result = $builder->process($context);
        $this->assertSame($context, $result);
    }

    #[Test]
    public function logout_if_token_is_expired()
    {
        $builder = new ConsoleLoginContextBuilder($this->cliStorage);
        $context = new ApieContext();
        $identifier = UserWithAddressIdentifier::createRandom();
        $address = new AddressWithZipcodeCheck(
            new DatabaseText('street'),
            new DatabaseText('12'),
            new DatabaseText('1234AB'),
            new DatabaseText('city')
        );
        $entity = new UserWithAddress($address, $identifier);
        $expiredToken = DecryptedAuthenticatedUser::createFromEntity(
            $entity,
            new BoundedContextId('ctx'),
            time() - 3600
        );
        $this->mockFileWriter->writeFile('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli', (string)$expiredToken);
        $context = $context->withContext(TextEncrypter::class, new class {
            public function decrypt($value)
            {
                return $value;
            }
        });
        $context = $context->withContext(ApieDatalayer::class, new class {
            public function find($id, $contextId)
            {
                return null;
            }
        });
        $result = $builder->process($context);
        $this->assertSame($context, $result);
        $this->assertFalse($this->mockFileWriter->fileExists('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli'));
    }

    #[Test]
    public function logout_if_authenticated_user_is_disabled()
    {
        $builder = new ConsoleLoginContextBuilder($this->cliStorage);
        $context = new ApieContext();
        $identifier = UserWithAddressIdentifier::createRandom();
        $address = new AddressWithZipcodeCheck(
            new DatabaseText('street'),
            new DatabaseText('12'),
            new DatabaseText('1234AB'),
            new DatabaseText('city')
        );
        $entity = new class($address, $identifier) extends UserWithAddress implements CheckLoginStatusInterface {
            public function isDisabled(): bool
            {
                return true;
            }
        };
        $token = DecryptedAuthenticatedUser::createFromEntity(
            $entity,
            new BoundedContextId('ctx'),
            time() + 3600
        );
        $this->mockFileWriter->writeFile('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli', (string)$token);
        $context = $context->withContext(TextEncrypter::class, new class {
            public function decrypt($value)
            {
                return $value;
            }
        });
        $context = $context->withContext(ApieDatalayer::class, new class {
            public function find($id, $contextId)
            {
                $address = new AddressWithZipcodeCheck(
                    new DatabaseText('street'),
                    new DatabaseText('12'),
                    new DatabaseText('1234AB'),
                    new DatabaseText('city')
                );
                return new class($address, $id) extends UserWithAddress implements CheckLoginStatusInterface {
                    public function isDisabled(): bool
                    {
                        return true;
                    }
                };
            }
        });
        $result = $builder->process($context);
        $this->assertSame($context, $result);
        $this->assertFalse($this->mockFileWriter->fileExists('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli'));
    }

    #[Test]
    public function encrypted_value_is_from_different_key_ignores_result()
    {
        $builder = new ConsoleLoginContextBuilder($this->cliStorage);
        $context = new ApieContext();
        $this->mockFileWriter->writeFile('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli', 'wrongkey');
        $context = $context->withContext(TextEncrypter::class, new class {
            public function decrypt($value)
            {
                throw new WrongKeyOrModifiedCiphertextException();
            }
        });
        $context = $context->withContext(ApieDatalayer::class, new class {
            public function find($id, $contextId)
            {
                return null;
            }
        });
        $result = $builder->process($context);
        $this->assertSame($context, $result);
        $this->assertTrue($this->mockFileWriter->fileExists('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli'));
    }

    #[Test]
    public function logout_if_user_not_found_in_datalayer()
    {
        $builder = new ConsoleLoginContextBuilder($this->cliStorage);
        $context = new ApieContext();
        $identifier = UserWithAddressIdentifier::createRandom();
        $address = new AddressWithZipcodeCheck(
            new DatabaseText('street'),
            new DatabaseText('12'),
            new DatabaseText('1234AB'),
            new DatabaseText('city')
        );
        $entity = new UserWithAddress($address, $identifier);
        $token = DecryptedAuthenticatedUser::createFromEntity(
            $entity,
            new BoundedContextId('ctx'),
            time() + 3600
        );
        $this->mockFileWriter->writeFile('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli', (string)$token);
        $context = $context->withContext(TextEncrypter::class, new class {
            public function decrypt($value)
            {
                return $value;
            }
        });
        $context = $context->withContext(ApieDatalayer::class, new class {
            public function find($id, $contextId)
            {
                throw new EntityNotFoundException($id);
            }
        });
        $result = $builder->process($context);
        $this->assertSame($context, $result);
        $this->assertFalse($this->mockFileWriter->fileExists('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli'));
    }

    #[Test]
    public function happy_flow_logged_in_with_consoleclistorage()
    {
        $builder = new ConsoleLoginContextBuilder($this->cliStorage);
        $context = new ApieContext();
        $identifier = UserWithAddressIdentifier::createRandom();
        $address = new AddressWithZipcodeCheck(
            new DatabaseText('street'),
            new DatabaseText('12'),
            new DatabaseText('1234AB'),
            new DatabaseText('city')
        );
        $entity = new UserWithAddress($address, $identifier);
        $token = DecryptedAuthenticatedUser::createFromEntity(
            $entity,
            new BoundedContextId('ctx'),
            time() + 3600
        );
        $this->mockFileWriter->writeFile('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli', (string)$token);
        $context = $context->withContext(TextEncrypter::class, new class {
            public function decrypt($value)
            {
                return $value;
            }
        });
        $context = $context->withContext(ApieDatalayer::class, new class($entity) {
            private $entity;
            public function __construct($entity)
            {
                $this->entity = $entity;
            }
            public function find($id, $contextId)
            {
                return $this->entity;
            }
        });
        $result = $builder->process($context);
        $this->assertNotSame($context, $result);
        $this->assertSame($entity, $result->getContext(ContextConstants::AUTHENTICATED_USER));
        $this->assertInstanceOf(DecryptedAuthenticatedUser::class, $result->getContext(DecryptedAuthenticatedUser::class));
        $this->assertTrue($this->mockFileWriter->fileExists('/tmp/home/.apie-' . md5('/tmp/root..._APIE_AUTHENTICATED') . '-cli'));
    }
}
