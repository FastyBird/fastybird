<?php declare(strict_types = 1);

/**
 * MetadataExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           24.06.20
 */

namespace FastyBird\Library\Metadata\DI;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use Nette;
use Nette\DI;

/**
 * Metadata extension container
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MetadataExtension extends DI\CompilerExtension
{

	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbMetadata',
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new MetadataExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition('schema.validator', new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Validator::class);

		$builder->addDefinition('loader.metadata', new DI\Definitions\ServiceDefinition())
			->setType(Loaders\MetadataLoader::class);

		$builder->addDefinition('loader.schema', new DI\Definitions\ServiceDefinition())
			->setType(Loaders\SchemaLoader::class);

		// Entity factories
		$builder->addDefinition('entity.factory.routing', new DI\Definitions\ServiceDefinition())
			->setType(Entities\RoutingFactory::class);

		$builder->addDefinition('entity.factory.actions.connector.action', new DI\Definitions\ServiceDefinition())
			->setType(Entities\Actions\ActionConnectorControlEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.actions.connector.property.action',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\Actions\ActionConnectorPropertyEntityFactory::class);

		$builder->addDefinition('entity.factory.actions.device.action', new DI\Definitions\ServiceDefinition())
			->setType(Entities\Actions\ActionDeviceControlEntityFactory::class);

		$builder->addDefinition('entity.factory.actions.device.property.action', new DI\Definitions\ServiceDefinition())
			->setType(Entities\Actions\ActionDevicePropertyEntityFactory::class);

		$builder->addDefinition('entity.factory.actions.channel.action', new DI\Definitions\ServiceDefinition())
			->setType(Entities\Actions\ActionChannelControlEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.actions.channel.property.action',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\Actions\ActionChannelPropertyEntityFactory::class);

		$builder->addDefinition('entity.factory.actions.trigger.action', new DI\Definitions\ServiceDefinition())
			->setType(Entities\Actions\ActionTriggerControlEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.accountsModule.account', new DI\Definitions\ServiceDefinition())
			->setType(Entities\AccountsModule\AccountEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.accountsModule.email', new DI\Definitions\ServiceDefinition())
			->setType(Entities\AccountsModule\EmailEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.accountsModule.identity',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\AccountsModule\IdentityEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.accountsModule.role', new DI\Definitions\ServiceDefinition())
			->setType(Entities\AccountsModule\RoleEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.triggersModule.action', new DI\Definitions\ServiceDefinition())
			->setType(Entities\TriggersModule\ActionEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.triggersModule.condition',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\TriggersModule\ConditionEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.triggersModule.notification',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\TriggersModule\NotificationEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.triggersModule.triggerControl',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\TriggersModule\TriggerControlEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.triggersModule.trigger', new DI\Definitions\ServiceDefinition())
			->setType(Entities\TriggersModule\TriggerEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.connector',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\ConnectorEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.connector.control',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\ConnectorControlEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.connector.property',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\ConnectorPropertyEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.devicesModule.device', new DI\Definitions\ServiceDefinition())
			->setType(Entities\DevicesModule\DeviceEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.device.control',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\DeviceControlEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.device.property',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\DevicePropertyEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.device.attribute',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\DeviceAttributeEntityFactory::class);

		$builder->addDefinition('entity.factory.modules.devicesModule.channel', new DI\Definitions\ServiceDefinition())
			->setType(Entities\DevicesModule\ChannelEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.channel.control',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\ChannelControlEntityFactory::class);

		$builder->addDefinition(
			'entity.factory.modules.devicesModule.channel.property',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Entities\DevicesModule\ChannelPropertyEntityFactory::class);
	}

}
