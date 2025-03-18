<?php
namespace Apie\Console\Helpers;

use Apie\Console\ApieInputHelper;
use Apie\Core\Context\ApieContext;
use Apie\Core\Context\MetadataFieldHashmap;
use Apie\Core\Metadata\CompositeMetadata;
use Apie\Core\Metadata\Fields\DiscriminatorColumn;
use Apie\Core\Metadata\Fields\FieldInterface;
use Apie\Core\Metadata\MetadataFactory;
use Apie\Core\Metadata\MetadataInterface;
use Apie\Core\Utils\ConverterUtils;
use Apie\Core\Utils\EntityUtils;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class PolymorphicObjectInteractor extends DefaultObjectInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        if ($metadata instanceof CompositeMetadata) {
            $class = $metadata->toClass();
            return $class !== null && EntityUtils::isPolymorphicEntity($class);
        }

        return false;
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
        $class = $metadata->toClass();
        assert($class !== null);
        $helper = $helperSet->get('question');
        assert($helper instanceof QuestionHelper);

        $list = EntityUtils::getDiscriminatorClasses($class)->toStringArray();
        $question = new ChoiceQuestion('Pick a value: ', array_combine($list, $list));
        $result = ConverterUtils::toReflectionClass($helper->ask($input, $output, $question));
        $output->writeln('');

        $childMetadata = MetadataFactory::getCreationMetadata(
            $result,
            $context
        );
        assert($childMetadata instanceof CompositeMetadata);
        $columns = [];
        $filteredMap = array_filter(
            $childMetadata->getHashmap()->toArray(),
            function (FieldInterface $field, string $propertyName) use (&$columns, $result) {
                if ($field instanceof DiscriminatorColumn) {
                    $columns[$propertyName] = $field->getValueForClass($result);
                    return false;
                }

                return true;
            },
            ARRAY_FILTER_USE_BOTH 
        );
        $childMetadata = new CompositeMetadata(
            new MetadataFieldHashmap($filteredMap),
            $childMetadata->toClass()
        );

        return array_merge(
            $columns,
            parent::interactWith(
                $childMetadata,
                $helperSet,
                $input,
                $output,
                $context
            )
        );
    }
}