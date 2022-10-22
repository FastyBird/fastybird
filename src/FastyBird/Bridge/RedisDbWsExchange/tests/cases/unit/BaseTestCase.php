<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbWsExchange\Tests\Cases\Unit;

use DateTimeImmutable;
use FastyBird\Bridge\RedisDbWsExchange;
use FastyBird\DateTimeFactory;
use Nette;
use Nette\DI;
use PHPUnit\Framework\TestCase;
use function constant;
use function defined;
use function file_exists;
use function md5;
use function time;

abstract class BaseTestCase extends TestCase
{

	protected DI\Container $container;

	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer();

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$this->mockContainerService(
			DateTimeFactory\Factory::class,
			$dateTimeFactory,
		);
	}

	protected function createContainer(string|null $additionalConfig = null): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../..';
		$vendorDir = defined('FB_VENDOR_DIR') ? constant('FB_VENDOR_DIR') : $rootDir . '/../vendor';

		$config = new Nette\Configurator();
		$config->setTempDirectory(FB_TEMP_DIR);

		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5((string) time())]]);
		$config->addParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		if ($additionalConfig !== null && file_exists($additionalConfig)) {
			$config->addConfig($additionalConfig);
		}

		RedisDbWsExchange\DI\RedisDbWsExchangeExtension::register($config);

		return $config->createContainer();
	}

	protected function mockContainerService(
		string $serviceType,
		object $serviceMock,
	): void
	{
		$foundServiceNames = $this->container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	private function replaceContainerService(string $serviceName, object $service): void
	{
		$this->container->removeService($serviceName);
		$this->container->addService($serviceName, $service);
	}

}
