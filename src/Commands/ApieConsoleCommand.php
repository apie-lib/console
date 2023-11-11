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
use Apie\Core\Metadata\MetadataFactory;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ApieConsoleCommand extends Command
{
    /**
     * @param ReflectionClass<EntityInterface> $reflectionClass
     */
    public function __construct(
        private readonly ActionInterface $apieFacadeAction,
        private readonly ApieContext $apieContext,
        private readonly ReflectionClass $reflectionClass,
        private readonly ApieInputHelper $apieInputHelper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $boundedContext = $this->apieContext->hasContext(BoundedContext::class)
            ? $this->apieContext->getContext(BoundedContext::class)
            : null;
        $this->setName('apie:' . ($boundedContext ? $boundedContext->getId() : 'unknown') . ':create-' . $this->reflectionClass->getShortName());
        $this->setHelp('This command allows you to create a ' . $this->reflectionClass->getShortName() .  ' instance');
        $this->addOption('interactive', 'i', InputOption::VALUE_NEGATABLE, 'Fill in the fields interactively');
        $metadata = MetadataFactory::getCreationMetadata(
            $this->reflectionClass,
            $this->apieContext
        );
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

    protected function execute(InputInterface $input, OutputInterface $output): int
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
        $apieContext = $this->apieContext->withContext(ContextConstants::RESOURCE_NAME, $this->reflectionClass->name);
        if ($input->getOption('interactive')) {
            $this->getHelperSet()->set($this->apieInputHelper);
            $rawContents += $this->apieInputHelper->interactUsingMetadata(
                MetadataFactory::getCreationMetadata(
                    $this->reflectionClass,
                    $this->apieContext
                ),
                $input,
                $output,
                $this->apieContext
            );
        }
        if ($output->isDebug()) {
            $output->writeln("<info>This will be the resource data to create the object:</info>");
            $output->writeln(json_encode($rawContents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    
        
        $response = ($this->apieFacadeAction)($apieContext, $rawContents);
        if (isset($response->resource)) {
            $output->writeln("<info>Resource was successfully created.</info>");
            return Command::SUCCESS;
        };
        $output->writeln('<error>' . $response->error->getMessage() . '</error>');
        if ($output->isDebug()) {
            $output->writeln('<error>' . $response->error->getTraceAsString() . '</error>');
        }
        return Command::FAILURE;
    }
}
