<?php
namespace Apie\Console\Helpers;

use Apie\Console\ApieInputHelper;
use Apie\Core\Context\ApieContext;
use Apie\Core\Enums\ScalarType;
use Apie\Core\Metadata\MetadataInterface;
use Apie\Core\Metadata\ScalarMetadata;
use Apie\Core\Metadata\Strategy\UnionTypeStrategy;
use Apie\Core\Metadata\UnionTypeMetadata;
use Apie\Core\Metadata\ValueObjectMetadata;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class UnionInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        return $metadata instanceof UnionTypeMetadata;
    }
    public function interactWith(
        MetadataInterface $metadata,
        HelperSet $helperSet,
        InputInterface $input,
        OutputInterface $output,
        ApieContext $context
    ): mixed {
        assert($metadata instanceof UnionTypeMetadata);
        $choices = [];
        /** @var array<string, MetadataInterface> */
        $mapping = [];
        foreach ($metadata->getTypes() as $metadata) {
            $name = match($metadata->toScalarType()) {
                ScalarType::STDCLASS => $metadata->toClass()?->name ?? 'object',
                default => $metadata->toScalarType()->value,
            };
            $choices[] = $name;
            $mapping[$name] = $metadata;
        }
        $choice = new ChoiceQuestion('Which type? ', $choices);
        $choice->setAutocompleterValues($choices);
        $questionHelper = $helperSet->get('question');
        assert($questionHelper instanceof QuestionHelper);
        $pickedChoice =  $questionHelper->ask($input, $output, $choice);
        $apieInputHelper = $helperSet->get('apie');
        assert($apieInputHelper instanceof ApieInputHelper);
        return $apieInputHelper->interactUsingMetadata($mapping[$pickedChoice], $input, $output, $context);
    }
}
