<?php
namespace Apie\Console\Helpers;

use Apie\Console\ApieInputHelper;
use Apie\Core\Context\ApieContext;
use Apie\Core\Metadata\ItemHashmapMetadata;
use Apie\Core\Metadata\MetadataInterface;
use Apie\TypeConverter\ReflectionTypeFactory;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ItemHashmapInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return $metadata instanceof ItemHashmapMetadata;
    }

    /**
     * @param ItemHashmapMetadata $metadata
     */
    public function interactWith(
        MetadataInterface $metadata,
        HelperSet $helperSet,
        InputInterface $input,
        OutputInterface $output,
        ApieContext $context
    ): mixed {
        $helper = $helperSet->get('question');
        assert($helper instanceof QuestionHelper);
        $apieInputHelper = $helperSet->get('apie');
        assert($apieInputHelper instanceof ApieInputHelper);
        $question = new ConfirmationQuestion('Add a new item to the list? (yes/no): ');
        $arrayType = $metadata->getArrayItemType();
        $result = [];
        while ($helper->ask($input, $output, $question)) {
            $key = $apieInputHelper->interactUsingTypehint(
                ReflectionTypeFactory::createReflectionType('string'),
                $input,
                $output,
                $context
            );
            $result[$key] = $apieInputHelper->interactUsingMetadata(
                $arrayType,
                $input,
                $output,
                $context
            );
        }
        return $result;
    }
}
