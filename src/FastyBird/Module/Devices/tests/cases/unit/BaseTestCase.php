<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit;

use DateTimeImmutable;
use Error;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Devices\DI;
use Nette;
use PHPUnit\Framework\TestCase;
use function constant;
use function defined;
use function getmypid;
use function in_array;
use function md5;
use function strval;
use function time;

abstract class BaseTestCase extends TestCase
{

	protected Nette\DI\Container|null $container = null;

	/** @var array<string> */
	protected array $neonFiles = [];

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Error
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$this->mockContainerService(
			DateTimeFactory\Factory::class,
			$dateTimeFactory,
		);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Error
	 */
	protected function mockContainerService(
		string $serviceType,
		object $serviceMock,
	): void
	{
		$container = $this->getContainer();
		$foundServiceNames = $container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Error
	 */
	protected function getContainer(): Nette\DI\Container
	{
		if ($this->container === null) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Error
	 */
	private function createContainer(): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../..';
		$vendorDir = defined('FB_VENDOR_DIR') ? constant('FB_VENDOR_DIR') : $rootDir . '/../vendor';

		$config = BootstrapBoot\Bootstrap::boot();
		$config->setForceReloadContainer();
		$config->setTempDirectory(FB_TEMP_DIR);

		$config->addStaticParameters(
			['container' => ['class' => 'SystemContainer_' . strval(getmypid()) . md5((string) time())]],
		);
		$config->addStaticParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		foreach ($this->neonFiles as $neonFile) {
			$config->addConfig($neonFile);
		}

		$config->setTimeZone('Europe/Prague');

		DI\DevicesExtension::register($config);

		$this->container = $config->createContainer();

		return $this->container;
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Error
	 */
	private function replaceContainerService(string $serviceName, object $service): void
	{
		$container = $this->getContainer();

		$container->removeService($serviceName);
		$container->addService($serviceName, $service);
	}

	protected function registerNeonConfigurationFile(string $file): void
	{
		if (!in_array($file, $this->neonFiles, true)) {
			$this->neonFiles[] = $file;
		}
	}

	protected function tearDown(): void
	{
		$this->container = null; // Fatal error: Cannot redeclare class SystemContainer

		parent::tearDown();
	}

}
