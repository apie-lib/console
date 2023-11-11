<?php
namespace Apie\Console\Helpers;

use Apie\Core\Context\ApieContext;
use Apie\Core\Enums\ScalarType;
use Apie\Core\Metadata\MetadataInterface;
use Apie\Core\Metadata\ScalarMetadata;
use Apie\Core\Metadata\ValueObjectMetadata;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class StringInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return ($metadata instanceof ScalarMetadata || $metadata instanceof ValueObjectMetadata)
            && $metadata->toScalarType() === ScalarType::STRING;
    }
    public function interactWith(
        MetadataInterface $metadata,
        HelperSet $helperSet,
        InputInterface $input,
        OutputInterface $output,
        ApieContext $context
    ): mixed {
        $helper = $helperSet->get('question');
        assert($helper instanceof QuestionHelper);
        $question = new Question('Please enter the value of this field: ');
        if ($metadata instanceof ValueObjectMetadata) {
            $question->setValidator(function ($input) use ($metadata) {
                return $metadata->toClass()->getMethod('fromNative')->invoke(null, $input)->toNative();
            });
        }
        return (string) $helper->ask($input, $output, $question);
    }
}
