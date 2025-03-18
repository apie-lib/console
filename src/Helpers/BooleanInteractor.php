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
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class BooleanInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return ($metadata instanceof ScalarMetadata || $metadata instanceof ValueObjectMetadata)
            && $metadata->toScalarType() === ScalarType::BOOLEAN;
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
        $question = new ConfirmationQuestion('On? (yes/no): ');
        if ($metadata instanceof ValueObjectMetadata) {
            $question->setValidator(function ($input) use ($metadata) {
                return $metadata->toClass()->getMethod('fromNative')->invoke(null, $input)->toNative();
            });
        }
        return $helper->ask($input, $output, $question);
    }
}
