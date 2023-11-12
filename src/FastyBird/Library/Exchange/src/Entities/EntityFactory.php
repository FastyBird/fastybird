<?php declare(strict_types = 1);

/**
 * EntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           13.06.22
 */

namespace FastyBird\Library\Exchange\Entities;

use FastyBird\Library\Exchange\Exceptions;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Throwable;

/**
 * Exchange entity factory
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EntityFactory
{

	public function __construct(
		private readonly MetadataEntities\EntityFactory $entityFactory,
		private readonly MetadataLoaders\SchemaLoader $schemaLoader,
		private readonly MetadataSchemas\Validator $validator,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function create(string $data, MetadataTypes\RoutingKey $routingKey): MetadataEntities\Entity
	{
		// ACTIONS
		if ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionConnectorControl::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionConnectorProperty::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionDeviceControl::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionDeviceProperty::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionChannelControl::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionChannelProperty::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ACTION)) {
			return $this->entityFactory->create(MetadataEntities\Actions\ActionTriggerControl::class, $data);

			// ACCOUNTS MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\AccountsModule\Account::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\AccountsModule\Email::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\AccountsModule\Identity::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\AccountsModule\Role::class, $data);

			// DEVICES MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\DevicesModule\Device::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/devices-module',
					'entity.device.property.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\PropertyType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\PropertyType::TYPE_DYNAMIC)) {
				return $this->entityFactory->create(MetadataEntities\DevicesModule\DeviceDynamicProperty::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
				return $this->entityFactory->create(
					MetadataEntities\DevicesModule\DeviceVariableProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)) {
				return $this->entityFactory->create(MetadataEntities\DevicesModule\DeviceMappedProperty::class, $data);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\DevicesModule\DeviceControl::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\DevicesModule\Channel::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/devices-module',
					'entity.channel.property.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\PropertyType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\PropertyType::TYPE_DYNAMIC)) {
				return $this->entityFactory->create(
					MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
				return $this->entityFactory->create(
					MetadataEntities\DevicesModule\ChannelVariableProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)) {
				return $this->entityFactory->create(MetadataEntities\DevicesModule\ChannelMappedProperty::class, $data);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\DevicesModule\ChannelControl::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\DevicesModule\Connector::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/devices-module',
					'entity.channel.property.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\PropertyType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\PropertyType::TYPE_DYNAMIC)) {
				return $this->entityFactory->create(
					MetadataEntities\DevicesModule\ConnectorDynamicProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)) {
				return $this->entityFactory->create(
					MetadataEntities\DevicesModule\ConnectorVariableProperty::class,
					$data,
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\DevicesModule\ConnectorControl::class, $data);

			// TRIGGERS MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'entity.trigger.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerType::TYPE_MANUAL)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\ManualTrigger::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerType::TYPE_AUTOMATIC)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\AutomaticTrigger::class, $data);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported trigger type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_DELETED)
		) {
			return $this->entityFactory->create(MetadataEntities\TriggersModule\TriggerControl::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace('schemas/modules/triggers-module', 'entity.action.json');

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerActionType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerActionType::TYPE_DUMMY)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\DummyAction::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerActionType::TYPE_DEVICE_PROPERTY)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\DevicePropertyAction::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerActionType::TYPE_CHANNEL_PROPERTY)) {
				return $this->entityFactory->create(
					MetadataEntities\TriggersModule\ChannelPropertyAction::class,
					$data,
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported action type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'entity.notification.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerNotificationType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerNotificationType::TYPE_EMAIL)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\EmailNotification::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerNotificationType::TYPE_SMS)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\SmsNotification::class, $data);
			} else {
				throw new Exceptions\InvalidArgument(
					'Provided data and routing key is for unsupported notification type',
				);
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'entity.condition.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerConditionType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_DUMMY)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\DummyCondition::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_DEVICE_PROPERTY)) {
				return $this->entityFactory->create(
					MetadataEntities\TriggersModule\DevicePropertyCondition::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_CHANNEL_PROPERTY)) {
				return $this->entityFactory->create(
					MetadataEntities\TriggersModule\ChannelPropertyCondition::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_TIME)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\TimeCondition::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_DATE)) {
				return $this->entityFactory->create(MetadataEntities\TriggersModule\DateCondition::class, $data);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported condition type');
			}
		}

		throw new Exceptions\InvalidState('Transformer could not be created');
	}

}
