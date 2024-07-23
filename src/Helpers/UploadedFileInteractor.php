<?php
namespace Apie\Console\Helpers;

use Apie\Core\Context\ApieContext;
use Apie\Core\Metadata\MetadataInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Mime\MimeTypes;

final class UploadedFileInteractor implements InputInteractorInterface
{
    public function supports(MetadataInterface $metadata): bool
    {
        $class = $metadata->toClass();
        if ($class?->name === UploadedFileInterface::class) {
            return true;
        }
        return $class && in_array(UploadedFileInterface::class, $class->getInterfaceNames());
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
        $question = new Question('Please enter a local file system file:');
        $question->setValidator(function ($input) {
            if ($input && file_exists($input) && is_readable($input) && is_file($input)) {
                return $input;
            }
            throw new RuntimeException('File "' . $input . '" is not a readable file or does not exist!');
        });
        $autocomplete = function (string $userInput): array {
            return glob($userInput . '*') ? : [];
        };
        $question->setAutocompleterCallback($autocomplete);
        $file = (string) $helper->ask($input, $output, $question);
        return [
            'contents' => file_get_contents($file),
            'mime' => MimeTypes::getDefault()->guessMimeType($file),
            'originalFilename' => basename($file),
        ];
    }
}
