<?php
namespace Apie\Tests\Console;

use Apie\Common\ActionDefinitionProvider;
use Apie\Common\Actions\CreateObjectAction;
use Apie\Common\Tests\Concerns\ProvidesApieFacade;
use Apie\Console\ConsoleCommandFactory;
use Apie\Core\Context\ApieContext;
use Apie\Fixtures\BoundedContextFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleCommandFactoryTest extends TestCase
{
    use ProvidesApieFacade;

    /**
     * @test
     */
    public function it_can_register_console_commands_for_a_bounded_context_and_run_it()
    {
        $boundedContext = BoundedContextFactory::createExample();
        $apieContext = new ApieContext([]);
        $testItem = new ConsoleCommandFactory(
            $this->givenAnApieFacade(CreateObjectAction::class),
            new ActionDefinitionProvider
        );
        $actual = $testItem->createForBoundedContext($boundedContext, $apieContext);
        $application = new Application();
        $application->setAutoExit(false);
        $application->addCommands($actual->toArray());

        $tester = new CommandTester($application->find('apie:default:create-UserWithAddress'));
        $tester->execute(
            [
             '--input-password' => 'Str0ngP4sw#rd',
             '--input-address' => '{"street":"evergreen terrace","streetNumber":742,"zipcode":"11111","city":"Springfield"}',
             '-vvv' => true,
            ]
        );
        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Resource was successfully created.', $output);
    }
}
