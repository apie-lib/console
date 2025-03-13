<?php
namespace Apie\Console;

use Apie\Console\Helpers\BooleanInteractor;
use Apie\Console\Helpers\DefaultObjectInteractor;
use Apie\Console\Helpers\EnumInteractor;
use Apie\Console\Helpers\InputInteractorInterface;
use Apie\Console\Helpers\ItemHashmapInteractor;
use Apie\Console\Helpers\ItemListInteractor;
use Apie\Console\Helpers\NullInteractor;
use Apie\Console\Helpers\NumberInteractor;
use Apie\Console\Helpers\PolymorphicObjectInteractor;
use Apie\Console\Helpers\StringInteractor;
use Apie\Console\Helpers\UnionInteractor;
use Apie\Console\Helpers\UploadedFileInteractor;
use Apie\Console\Helpers\VerifyOtpInteractor;
use Apie\Core\Context\ApieContext;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;
use ReflectionType;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ApieInputHelper extends Helper
{
    /** @var array<int, InputInteractorInterface> */
    private array $inputInteractors;

    public function __construct(InputInteractorInterface... $inputInteractors)
    {
        $this->inputInteractors = $inputInteractors;
    }

    /**
     * @param iterable<int, InputInteractorInterface> $additionalInteractors
     */
    public static function create(iterable $additionalInteractors): self
    {
        return new self(...[
            ...$additionalInteractors,
            new VerifyOtpInteractor(),
            new UploadedFileInteractor(),
            new ItemListInteractor(),
            new ItemHashmapInteractor(),
            new EnumInteractor(),
            new UnionInteractor(),
            new StringInteractor(),
            new NullInteractor(),
            new NumberInteractor(),
            new BooleanInteractor(),
            new PolymorphicObjectInteractor(),
            new DefaultObjectInteractor()
        ]);
    }

    public function getName(): string
    {
        return 'apie';
    }

    public function interactUsingTypehint(
        ReflectionType $type,
        InputInterface $input,
        OutputInterface $output,
        ?ApieContext $context = null
    ): mixed {
        if (!$input->isInteractive()) {
            throw new \RuntimeException('Input is not interactive!');
        }
        $context??= new ApieContext();
        $metadata = MetadataFactory::getCreationMetadata($type, $context);
        return $this->interactUsingMetadata($metadata, $input, $output, $context);
    }

    public function interactUsingMetadata(
        MetadataInterface $metadata,
        InputInterface $input,
        OutputInterface $output,
        ?ApieContext $context = null
    ): mixed {
        if (!$input->isInteractive()) {
            throw new \RuntimeException('Input is not interactive!');
        }
        $context??= new ApieContext();
        foreach ($this->inputInteractors as $inputInteractor) {
            if ($inputInteractor->supports($metadata)) {
                return $inputInteractor->interactWith(
                    $metadata,
                    $this->getHelperSet(),
                    $input,
                    $output,
                    $context
                );
            }
        }
        throw new \RuntimeException('I can not interact with metadata class: ' . get_debug_type($metadata));
    }
}
