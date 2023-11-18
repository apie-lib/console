<?php
namespace Apie\Console\Helpers;

use Apie\Core\Context\ApieContext;
use Apie\Core\Enums\ScalarType;
use Apie\Core\Metadata\MetadataInterface;
use Apie\Core\Metadata\ScalarMetadata;
use Apie\Core\Metadata\ValueObjectMetadata;
use Apie\Core\ValueObjects\IsPasswordValueObject;
use Exception;
use LogicException;
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
            if (in_array(IsPasswordValueObject::class, $metadata->toClass()->getTraitNames())) {
                $question->setHidden(true);
                $firstEnteredPassword = (string) $helper->ask($input, $output, $question);
                $question = new Question('Please type again: ');
                $question->setHidden(true);
                $question->setMaxAttempts(1);
                $question->setValidator(function ($input) use ($metadata, $firstEnteredPassword) {
                    try {
                        $result = $metadata->toClass()->getMethod('fromNative')->invoke(null, $input)->toNative();
                    } catch (Exception $error) {
                        $result = $input;
                    }
                    if ($result !== $firstEnteredPassword) {
                        throw new LogicException('You did not enter the same password twice!');
                    }
                    return $result;
                });
            }
        }
        return (string) $helper->ask($input, $output, $question);
    }
}
