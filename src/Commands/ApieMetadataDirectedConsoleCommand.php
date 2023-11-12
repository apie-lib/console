<?php
namespace Apie\Console\Commands;

use Apie\Common\ContextConstants;
use Apie\Console\ApieInputHelper;
use Apie\Core\Actions\ActionInterface;
use Apie\Core\BoundedContext\BoundedContext;
use Apie\Core\Context\ApieContext;
use Apie\Core\Entities\EntityInterface;
use Apie\Core\Metadata\Fields\FieldInterface;
use Apie\Core\Metadata\Fields\FieldWithPossibleDefaultValue;
use Apie\Core\Metadata\MetadataInterface;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ApieMetadataDirectedConsoleCommand extends Command
{
    /**
     * @param ReflectionClass<EntityInterface> $reflectionClass
     */
    final public function __construct(
        protected readonly ActionInterface $apieFacadeAction,
        protected readonly ApieContext $apieContext,
        protected readonly ReflectionClass $reflectionClass,
        protected readonly ApieInputHelper $apieInputHelper
    ) {
        parent::__construct();
    }

    abstract protected function getCommandName(): string;

    abstract protected function getCommandHelp(): string;

    abstract protected function getSucessMessage(): string;

    abstract protected function getMetadata(): MetadataInterface;

    final protected function configure(): void
    {
        $boundedContext = $this->apieContext->hasContext(BoundedContext::class)
            ? $this->apieContext->getContext(BoundedContext::class)
            : null;
        $this->setName('apie:' . ($boundedContext ? $boundedContext->getId() : 'unknown') . ':'. $this->getCommandName());
        $this->setHelp($this->getCommandHelp());
        $this->addOption('interactive', 'i', InputOption::VALUE_NEGATABLE, 'Fill in the fields interactively');
        $metadata = $this->getMetadata();
        foreach ($metadata->getHashmap() as $fieldName => $field) {
            $this->addInputOption($fieldName, $field);
        }
    }

    private function addInputOption(string $name, FieldInterface $field): void
    {
        if (!$field->isField()) {
            return;
        }
        $flags = $field->isRequired() ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
        //if ($parameter->isVariadic()) {
        //    $flags |= InputOption::VALUE_IS_ARRAY;
        //}

        if ($field instanceof FieldWithPossibleDefaultValue && $field->hasDefaultValue()) {
            $this->addOption(
                'input-' . $name,
                null,
                $flags,
                'provide ' . $name . ' value',
                $field->getDefaultValue()
            );
        } else {
            $this->addOption(
                'input-' . $name,
                null,
                $flags,
                'provide ' . $name . ' value'
            );
        }
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rawContents = [];
        foreach ($input->getOptions() as $optionName => $optionValue) {
            if (str_starts_with($optionName, 'input-')) {
                if ($optionValue === null) {
                    continue;
                }
                $data = json_decode($optionValue, true);
                if (json_last_error()) {
                    $rawContents[substr($optionName, strlen('input-'))] = $optionValue;
                } else {
                    $rawContents[substr($optionName, strlen('input-'))] = $data;
                }
            }
        }
        $apieContext = $this->apieContext
            ->withContext(ContextConstants::RESOURCE_NAME, $this->reflectionClass->name);
        if ($input->getOption('interactive')) {
            $this->getHelperSet()->set($this->apieInputHelper);
            $rawContents += $this->apieInputHelper->interactUsingMetadata(
                $this->getMetadata(),
                $input,
                $output,
                $this->apieContext
            );
        }
        if ($output->isDebug()) {
            $output->writeln("<info>Raw data entered:</info>");
            $output->writeln(json_encode($rawContents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        $response = ($this->apieFacadeAction)($apieContext, $rawContents);
        if (isset($response->resource)) {
            $output->writeln('<info>' . $this->getSucessMessage() . '</info>');
            return Command::SUCCESS;
        };
        $output->writeln('<error>' . $response->error->getMessage() . '</error>');
        if ($output->isDebug()) {
            $output->writeln('<error>' . $response->error->getTraceAsString() . '</error>');
        }
        return Command::FAILURE;
    }
}
