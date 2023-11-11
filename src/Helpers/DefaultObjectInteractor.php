<?php
namespace Apie\Console\Helpers;

use Apie\Console\ApieInputHelper;
use Apie\Core\Context\ApieContext;
use Apie\Core\Metadata\CompositeMetadata;
use Apie\Core\Metadata\Fields\SetterMethod;
use Apie\Core\Metadata\MetadataInterface;
use Apie\TypeConverter\ReflectionTypeFactory;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultObjectInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return $metadata instanceof CompositeMetadata;
    }
    public function interactWith(
        MetadataInterface $metadata,
        HelperSet $helperSet,
        InputInterface $input,
        OutputInterface $output,
        ApieContext $context
    ): mixed {
        $apieInputHelper = $helperSet->get('apie');
        assert($apieInputHelper instanceof ApieInputHelper);
        assert($metadata instanceof CompositeMetadata);
    
        $result = [];
        foreach ($metadata->getHashmap() as $field => $fieldMeta) {
            if ($fieldMeta instanceof SetterMethod) {
                $output->writeln('Field: ' . $field);
                // TODO indent with some IndentOutputDecorator?
                $typehint = $fieldMeta->getTypehint();
                if (!$typehint) {
                    $typehint = ReflectionTypeFactory::createReflectionType('mixed');
                }
                $result[$field] = $apieInputHelper->interactUsingTypehint($typehint, $input, $output, $context);
            }
        }
        return $result;
    }
}