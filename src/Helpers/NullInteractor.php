<?php
namespace Apie\Console\Helpers;

use Apie\Core\Context\ApieContext;
use Apie\Core\Enums\ScalarType;
use Apie\Core\Metadata\MetadataInterface;
use Apie\Core\Metadata\ScalarMetadata;
use Apie\Core\Metadata\ValueObjectMetadata;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class NullInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return ($metadata instanceof ScalarMetadata || $metadata instanceof ValueObjectMetadata)
            && $metadata->toScalarType() === ScalarType::NULL;
    }
    public function interactWith(
        MetadataInterface $metadata,
        HelperSet $helperSet,
        InputInterface $input,
        OutputInterface $output,
        ApieContext $context
    ): mixed {
        return null;
    }
}
