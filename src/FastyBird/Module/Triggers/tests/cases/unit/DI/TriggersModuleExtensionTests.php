<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Module\Triggers\Commands;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\DI;
use FastyBird\Module\Triggers\Hydrators;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use FastyBird\Module\Triggers\Subscribers;
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

		Assert::notNull($container->getByType(Models\Triggers\TriggersRepository::class));
		Assert::notNull($container->getByType(Models\Triggers\Controls\ControlsRepository::class));
		Assert::notNull($container->getByType(Models\Actions\ActionsRepository::class));
		Assert::notNull($container->getByType(Models\Notifications\NotificationsRepository::class));
		Assert::notNull($container->getByType(Models\Conditions\ConditionsRepository::class));

		Assert::notNull($container->getByType(Models\Triggers\TriggersManager::class));
		Assert::notNull($container->getByType(Models\Triggers\Controls\ControlsManager::class));
		Assert::notNull($container->getByType(Models\Actions\ActionsManager::class));
		Assert::notNull($container->getByType(Models\Notifications\NotificationsManager::class));
		Assert::notNull($container->getByType(Models\Conditions\ConditionsManager::class));

		Assert::notNull($container->getByType(Models\States\ActionsRepository::class));
		Assert::notNull($container->getByType(Models\States\ConditionsRepository::class));

		Assert::notNull($container->getByType(Models\States\ActionsManager::class));
		Assert::notNull($container->getByType(Models\States\ConditionsManager::class));

		Assert::notNull($container->getByType(Controllers\TriggersV1::class));
		Assert::notNull($container->getByType(Controllers\TriggerControlsV1::class));
		Assert::notNull($container->getByType(Controllers\ActionsV1::class));
		Assert::notNull($container->getByType(Controllers\NotificationsV1::class));
		Assert::notNull($container->getByType(Controllers\ConditionsV1::class));

		Assert::notNull($container->getByType(Schemas\Triggers\AutomaticTrigger::class));
		Assert::notNull($container->getByType(Schemas\Triggers\ManualTrigger::class));
		Assert::notNull($container->getByType(Schemas\Triggers\Controls\Control::class));
		Assert::notNull($container->getByType(Schemas\Actions\DevicePropertyAction::class));
		Assert::notNull($container->getByType(Schemas\Actions\ChannelPropertyAction::class));
		Assert::notNull($container->getByType(Schemas\Notifications\EmailNotification::class));
		Assert::notNull($container->getByType(Schemas\Notifications\SmsNotification::class));
		Assert::notNull($container->getByType(Schemas\Conditions\ChannelPropertyCondition::class));
		Assert::notNull($container->getByType(Schemas\Conditions\DevicePropertyCondition::class));
		Assert::notNull($container->getByType(Schemas\Conditions\DateCondition::class));
		Assert::notNull($container->getByType(Schemas\Conditions\TimeCondition::class));

		Assert::notNull($container->getByType(Hydrators\Triggers\AutomaticTrigger::class));
		Assert::notNull($container->getByType(Hydrators\Triggers\ManualTrigger::class));
		Assert::notNull($container->getByType(Hydrators\Actions\DevicePropertyAction::class));
		Assert::notNull($container->getByType(Hydrators\Actions\ChannelPropertyAction::class));
		Assert::notNull($container->getByType(Hydrators\Notifications\EmailNotification::class));
		Assert::notNull($container->getByType(Hydrators\Notifications\SmsNotification::class));
		Assert::notNull($container->getByType(Hydrators\Conditions\ChannelPropertyCondition::class));
		Assert::notNull($container->getByType(Hydrators\Conditions\DevicePropertyCondition::class));
		Assert::notNull($container->getByType(Hydrators\Conditions\DataCondition::class));
		Assert::notNull($container->getByType(Hydrators\Conditions\TimeCondition::class));

		Assert::notNull($container->getByType(Router\Validator::class));
		Assert::notNull($container->getByType(Router\Routes::class));

		Assert::notNull($container->getByType(Subscribers\ModuleEntities::class));
		Assert::notNull($container->getByType(Subscribers\ActionEntity::class));
		Assert::notNull($container->getByType(Subscribers\ConditionEntity::class));
		Assert::notNull($container->getByType(Subscribers\NotificationEntity::class));
	}

	/**
	 * @return Nette\DI\Container
	 */
	protected function createContainer(): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../../../';

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5((string) time())]]);
		$config->addParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir]);

		$config->addConfig(__DIR__ . '/../../../common.neon');

		DI\TriggersExtension::register($config);

		return $config->createContainer();
	}

}

$test_case = new ServicesTest();
$test_case->run();
