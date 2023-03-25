<?php declare(strict_types = 1);

namespace Tests\Cases;

use DateTimeImmutable;
use FastyBird\DateTimeFactory;
use FastyBird\Plugin\RabbitMq;
use Mockery;
use Nette;
use Nette\DI;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;

abstract class BaseTestCase extends BaseMockeryTestCase
{

	/** @var DI\Container */
	protected $container;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer();

		$dateTimeFactory = Mockery::mock(DateTimeFactory\DateTimeFactory::class);
		$dateTimeFactory
			->shouldReceive('getNow')
			->andReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$this->mockContainerService(
			DateTimeFactory\DateTimeFactory::class,
			$dateTimeFactory
		);
	}

	/**
	 * @param string|null $additionalConfig
	 *
	 * @return Nette\DI\Container
	 */
	protected function createContainer(?string $additionalConfig = null): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../../';

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5((string) time())]]);
		$config->addParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		if ($additionalConfig && file_exists($additionalConfig)) {
			$config->addConfig($additionalConfig);
		}

		RabbitMqPlugin\DI\RabbitMqPluginExtension::register($config);

		return $config->createContainer();
	}

	/**
	 * @param string $serviceType
	 * @param object $serviceMock
	 *
	 * @return void
	 */
	protected function mockContainerService(
		string $serviceType,
		object $serviceMock
	): void {
		$foundServiceNames = $this->container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	/**
	 * @param string $serviceName
	 * @param object $service
	 *
	 * @return void
	 */
	private function replaceContainerService(string $serviceName, object $service): void
	{
		$this->container->removeService($serviceName);
		$this->container->addService($serviceName, $service);
	}

}
