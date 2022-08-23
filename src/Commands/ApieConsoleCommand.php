<?php
namespace Apie\Console\Commands;

use Apie\Common\ApieFacadeAction;
use Apie\Core\BoundedContext\BoundedContext;
use Apie\Core\Context\ApieContext;
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
    protected static $defaultDescription = 'Creates resource.';

    
    public function __construct(private readonly ApieFacadeAction $apieFacadeAction, private readonly ApieContext $apieContext, private readonly ReflectionClass $reflectionClass)
    {
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
                    'provide ' . $parameter->getName() . ' value',
                    $setter->getDefaultValue()
                );
            }
        }
    }

    private function addInputOption(string $name, ReflectionParameter $parameter): void
    {
        $flags = 0;
        if (!$parameter->isOptional()) {
            $flags |= InputOption::VALUE_REQUIRED;
        }
        if ($parameter->isVariadic()) {
            $flags |= InputOption::VALUE_IS_ARRAY;
        }

        $this->addOption(
            'input-' . $name,
            null,
            $flags,
            'provide ' . $name . ' value'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rawContents = [];
        foreach ($input->getOptions() as $optionName => $optionValue) {
            if (str_starts_with($optionName, 'input-')) {
                $rawContents[substr($optionName, strlen('input-'))] = $optionValue;
            }
        }
        $response = ($this->apieFacadeAction)($this->apieContext, $rawContents);
        if (isset($response->resource)) {
            return Command::SUCCESS;
        };
        return Command::FAILURE;
    }
}
