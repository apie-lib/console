<?php
namespace Apie\Console\Helpers;

use Apie\Core\Context\ApieContext;
use Apie\Core\Metadata\EnumMetadata;
use Apie\Core\Metadata\MetadataInterface;
use Apie\Core\Utils\EnumUtils;
use ReflectionEnum;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class EnumInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return $metadata instanceof EnumMetadata;
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
        $class = $metadata->toClass();
        assert($class instanceof ReflectionEnum);
        $question = new ChoiceQuestion('Pick a value: ', EnumUtils::getValues($class));
        return $helper->ask($input, $output, $question);
    }
}
