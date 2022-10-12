<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule\Commands;
use FastyBird\DevicesModule\Controllers;
use FastyBird\DevicesModule\DI;
use FastyBird\DevicesModule\Hydrators;
use FastyBird\DevicesModule\Middleware;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Router;
use FastyBird\DevicesModule\Schemas;
use FastyBird\DevicesModule\Subscribers;
use Nette;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ServicesTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		Assert::notNull($container->getByType(Commands\Initialize::class));

		Assert::notNull($container->getByType(Middleware\Access::class));

		Assert::notNull($container->getByType(Models\Devices\DevicesRepository::class));
		Assert::notNull($container->getByType(Models\Devices\Properties\PropertiesRepository::class));
		Assert::notNull($container->getByType(Models\Devices\Controls\ControlsRepository::class));
		Assert::notNull($container->getByType(Models\Channels\ChannelsRepository::class));
		Assert::notNull($container->getByType(Models\Channels\Properties\PropertiesRepository::class));
		Assert::notNull($container->getByType(Models\Channels\Controls\ControlsRepository::class));
		Assert::notNull($container->getByType(Models\Connectors\ConnectorsRepository::class));
		Assert::notNull($container->getByType(Models\Connectors\Controls\ControlsRepository::class));

		Assert::notNull($container->getByType(Models\Devices\DevicesManager::class));
		Assert::notNull($container->getByType(Models\Devices\Properties\PropertiesManager::class));
		Assert::notNull($container->getByType(Models\Devices\Controls\ControlsManager::class));
		Assert::notNull($container->getByType(Models\Channels\ChannelsManager::class));
		Assert::notNull($container->getByType(Models\Channels\Properties\PropertiesManager::class));
		Assert::notNull($container->getByType(Models\Channels\Controls\ControlsManager::class));
		Assert::notNull($container->getByType(Models\Connectors\ConnectorsManager::class));
		Assert::notNull($container->getByType(Models\Connectors\Controls\ControlsManager::class));

		Assert::notNull($container->getByType(Models\States\ConnectorPropertiesRepository::class));
		Assert::notNull($container->getByType(Models\States\DevicePropertiesRepository::class));
		Assert::notNull($container->getByType(Models\States\ChannelPropertiesRepository::class));

		Assert::notNull($container->getByType(Models\States\ConnectorPropertiesManager::class));
		Assert::notNull($container->getByType(Models\States\DevicePropertiesManager::class));
		Assert::notNull($container->getByType(Models\States\ChannelPropertiesManager::class));

		Assert::notNull($container->getByType(Controllers\DevicesV1::class));
		Assert::notNull($container->getByType(Controllers\DeviceChildrenV1::class));
		Assert::notNull($container->getByType(Controllers\DevicePropertiesV1::class));
		Assert::notNull($container->getByType(Controllers\DevicePropertyChildrenV1::class));
		Assert::notNull($container->getByType(Controllers\DeviceControlsV1::class));
		Assert::notNull($container->getByType(Controllers\ChannelsV1::class));
		Assert::notNull($container->getByType(Controllers\ChannelPropertiesV1::class));
		Assert::notNull($container->getByType(Controllers\ChannelPropertyChildrenV1::class));
		Assert::notNull($container->getByType(Controllers\ChannelControlsV1::class));
		Assert::notNull($container->getByType(Controllers\ConnectorsV1::class));
		Assert::notNull($container->getByType(Controllers\ConnectorControlsV1::class));

		Assert::notNull($container->getByType(Schemas\Devices\Blank::class));
		Assert::notNull($container->getByType(Schemas\Devices\Properties\Dynamic::class));
		Assert::notNull($container->getByType(Schemas\Devices\Properties\Variable::class));
		Assert::notNull($container->getByType(Schemas\Devices\Controls\Control::class));
		Assert::notNull($container->getByType(Schemas\Channels\Channel::class));
		Assert::notNull($container->getByType(Schemas\Channels\Properties\Dynamic::class));
		Assert::notNull($container->getByType(Schemas\Channels\Properties\Variable::class));
		Assert::notNull($container->getByType(Schemas\Channels\Controls\Control::class));
		Assert::notNull($container->getByType(Schemas\Connectors\Blank::class));
		Assert::notNull($container->getByType(Schemas\Connectors\Controls\Control::class));

		Assert::notNull($container->getByType(Hydrators\Devices\Blank::class));
		Assert::notNull($container->getByType(Hydrators\Channels\Channel::class));
		Assert::notNull($container->getByType(Hydrators\Properties\DeviceDynamic::class));
		Assert::notNull($container->getByType(Hydrators\Properties\DeviceVariable::class));
		Assert::notNull($container->getByType(Hydrators\Properties\ChannelDynamic::class));
		Assert::notNull($container->getByType(Hydrators\Properties\ChannelVariable::class));
		Assert::notNull($container->getByType(Hydrators\Connectors\Blank::class));

		Assert::notNull($container->getByType(Router\Validator::class));
		Assert::notNull($container->getByType(Router\Routes::class));

		Assert::notNull($container->getByType(Subscribers\ModuleEntities::class));
	}

	/**
	 * @return Nette\DI\Container
	 */
	protected function createContainer(): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../../../../tests/';

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5((string) time())]]);
		$config->addParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir]);

		$config->addConfig(__DIR__ . '/../../../common.neon');

		DI\DevicesModuleExtension::register($config);

		return $config->createContainer();
	}

}

$test_case = new ServicesTest();
$test_case->run();
