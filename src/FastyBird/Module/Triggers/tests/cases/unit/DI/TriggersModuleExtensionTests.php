<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Triggers\Commands;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Hydrators;
use FastyBird\Module\Triggers\Middleware;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use FastyBird\Module\Triggers\Subscribers;
use FastyBird\Module\Triggers\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Triggers\Utilities;
use Nette;
use RuntimeException;

final class TriggersModuleExtensionTests extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Commands\Initialize::class, false));

		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Triggers\TriggersRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Triggers\Controls\ControlsRepository::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Actions\ActionsRepository::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Notifications\NotificationsRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Conditions\ConditionsRepository::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Triggers\TriggersManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Triggers\Controls\ControlsManager::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Actions\ActionsManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Conditions\ConditionsManager::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Models\States\ActionsRepository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\ConditionsRepository::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\States\ActionsManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\ConditionsManager::class, false));

		self::assertNotNull($this->getContainer()->getByType(Controllers\TriggersV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\TriggerControlsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ActionsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\NotificationsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ConditionsV1::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Triggers\AutomaticTrigger::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Triggers\ManualTrigger::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Triggers\Controls\Control::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Notifications\EmailNotification::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Notifications\SmsNotification::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Triggers\AutomaticTrigger::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Triggers\ManualTrigger::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Notifications\EmailNotification::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Notifications\SmsNotification::class, false));

		self::assertNotNull($this->getContainer()->getByType(Router\Validator::class, false));
		self::assertNotNull($this->getContainer()->getByType(Router\Routes::class, false));

		self::assertNotNull($this->getContainer()->getByType(Middleware\Access::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\ModuleEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\NotificationEntity::class, false));

		self::assertNotNull($this->getContainer()->getByType(Utilities\Database::class, false));
	}

}
