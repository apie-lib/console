<?php
namespace Apie\Console\Commands;

use Apie\Common\ContextConstants;
use Apie\Core\Actions\ActionInterface;
use Apie\Core\BoundedContext\BoundedContext;
use Apie\Core\Context\ApieContext;
use Apie\Core\Entities\EntityInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
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
        private readonly ReflectionClass $reflectionClass
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
        
        $constructor = $this->reflectionClass->getConstructor();
        $names = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $this->addInputOption($parameter->getName(), $parameter);
                $names[$parameter->getName()] = true;
            }
        }

        foreach ($this->apieContext->getApplicableSetters($this->reflectionClass) as $key => $setter) {
            if (isset($names[$key])) {
                continue;
            }
            if ($setter instanceof ReflectionMethod) {
                $parameters = $setter->getParameters();
                $lastParameter = end($parameters);
                $this->addInputOption($key, $lastParameter);
            }
            if ($setter instanceof ReflectionProperty) {
                $this->addOption(
                    'input-' . $key,
                    null,
                    $setter->hasDefaultValue() ? InputOption::VALUE_OPTIONAL : 0,
                    'provide ' . $setter->getName() . ' value',
                    $setter->getDefaultValue()
                );
            }
        }
    }

    private function addInputOption(string $name, ReflectionParameter $parameter): void
    {
        $flags = $parameter->isOptional() ? InputOption::VALUE_OPTIONAL : InputOption::VALUE_REQUIRED;
        if ($parameter->isVariadic()) {
            $flags |= InputOption::VALUE_IS_ARRAY;
        }

        if ($parameter->isDefaultValueAvailable()) {
            $this->addOption(
                'input-' . $name,
                null,
                $flags,
                'provide ' . $name . ' value',
                $parameter->getDefaultValue()
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
        if ($output->isDebug()) {
            $output->writeln("<info>This will be the resource data to create the object:</info>");
            $output->writeln(json_encode($rawContents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        $apieContext = $this->apieContext->withContext(ContextConstants::RESOURCE_NAME, $this->reflectionClass->name);
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
