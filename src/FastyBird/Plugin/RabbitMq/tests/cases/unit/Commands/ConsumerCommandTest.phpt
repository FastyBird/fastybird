<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Plugin\RabbitMq\Commands;
use FastyBird\Plugin\RabbitMq\Exchange;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Psr\Log;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ConsumerCommandTest extends BaseMockeryTestCase
{

	public function testExecute(): void
	{
		$exchange = Mockery::mock(Exchange::class);
		$exchange
			->shouldReceive('initialize')
			->times(1)
			->getMock()
			->shouldReceive('run')
			->times(1);

		$logger = Mockery::mock(Log\LoggerInterface::class);
		$logger
			->shouldReceive('info')
			->withArgs(['[FB:PLUGIN:RABBITMQ] Starting exchange queue consumer'])
			->times(1);

		$application = new Application();
		$application->add(new Commands\ConsumerCommand(
			$exchange,
			$logger
		));

		$command = $application->get('fb:rabbit-consumer:start');

		$commandTester = new CommandTester($command);
		$commandTester->execute([]);

		Assert::true(true);
	}

}

$test_case = new ConsumerCommandTest();
$test_case->run();
