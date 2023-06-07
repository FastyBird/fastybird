<?php declare(strict_types = 1);

namespace FastyBird\Plugin\WsServer\Tests\Cases\Unit;

use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Plugin\WsServer;
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

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer();
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 */
	protected function createContainer(string|null $additionalConfig = null): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../..';
		$vendorDir = defined('FB_VENDOR_DIR') ? constant('FB_VENDOR_DIR') : $rootDir . '/../vendor';

		$config = BootstrapBoot\Bootstrap::boot();
		$config->setForceReloadContainer();
		$config->setTempDirectory(FB_TEMP_DIR);

		$config->addStaticParameters(['container' => ['class' => 'SystemContainer_' . md5((string) time())]]);
		$config->addStaticParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		if ($additionalConfig !== null && file_exists($additionalConfig)) {
			$config->addConfig($additionalConfig);
		}

		$config->setTimeZone('Europe/Prague');

		WsServer\DI\WsServerExtension::register($config);

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