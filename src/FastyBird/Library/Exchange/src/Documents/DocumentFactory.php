<?php declare(strict_types = 1);

/**
 * DocumentFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           13.06.22
 */

namespace FastyBird\Library\Exchange\Documents;

use FastyBird\Library\Exchange\Exceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Throwable;

/**
 * Exchange document factory
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DocumentFactory
{

	public function __construct(
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
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
	public function create(string $data, MetadataTypes\RoutingKey $routingKey): MetadataDocuments\Document
	{
		// ACTIONS
		if ($routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_CONTROL_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionConnectorControl::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionConnectorProperty::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_CONTROL_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionDeviceControl::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionDeviceProperty::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_CONTROL_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionChannelControl::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionChannelProperty::class, $data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONTROL_ACTION)) {
			return $this->documentFactory->create(MetadataDocuments\Actions\ActionTriggerControl::class, $data);

			// ACCOUNTS MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ACCOUNT_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ACCOUNT_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ACCOUNT_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ACCOUNT_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\AccountsModule\Account::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::EMAIL_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::EMAIL_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::EMAIL_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::EMAIL_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\AccountsModule\Email::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::IDENTITY_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::IDENTITY_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::IDENTITY_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::IDENTITY_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\AccountsModule\Identity::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROLE_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROLE_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROLE_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROLE_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\AccountsModule\Role::class, $data);

			// DEVICES MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\DevicesModule\Device::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/devices-module',
					'document.device.property.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\PropertyType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\PropertyType::TYPE_DYNAMIC)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\DeviceMappedProperty::class,
					$data,
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::DEVICE_CONTROL_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\DevicesModule\DeviceControl::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\DevicesModule\Channel::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/devices-module',
					'document.channel.property.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\PropertyType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\PropertyType::TYPE_DYNAMIC)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\ChannelMappedProperty::class,
					$data,
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CHANNEL_CONTROL_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\DevicesModule\ChannelControl::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\DevicesModule\Connector::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/devices-module',
					'document.connector.property.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\PropertyType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\PropertyType::TYPE_DYNAMIC)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
				return $this->documentFactory->create(
					MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
					$data,
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::CONNECTOR_CONTROL_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\DevicesModule\ConnectorControl::class, $data);

			// TRIGGERS MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'document.trigger.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerType::TYPE_MANUAL)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\ManualTrigger::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerType::TYPE_AUTOMATIC)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\AutomaticTrigger::class, $data);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported trigger type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONTROL_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONTROL_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONTROL_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONTROL_DOCUMENT_DELETED)
		) {
			return $this->documentFactory->create(MetadataDocuments\TriggersModule\TriggerControl::class, $data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_ACTION_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_ACTION_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_ACTION_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_ACTION_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'document.action.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerActionType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerActionType::TYPE_DUMMY)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\DummyAction::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerActionType::TYPE_DEVICE_PROPERTY)) {
				return $this->documentFactory->create(
					MetadataDocuments\TriggersModule\DevicePropertyAction::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\TriggerActionType::TYPE_CHANNEL_PROPERTY)) {
				return $this->documentFactory->create(
					MetadataDocuments\TriggersModule\ChannelPropertyAction::class,
					$data,
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported action type');
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_NOTIFICATION_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_NOTIFICATION_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_NOTIFICATION_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_NOTIFICATION_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'document.notification.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerNotificationType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerNotificationType::TYPE_EMAIL)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\EmailNotification::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerNotificationType::TYPE_SMS)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\SmsNotification::class, $data);
			} else {
				throw new Exceptions\InvalidArgument(
					'Provided data and routing key is for unsupported notification type',
				);
			}
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONDITION_DOCUMENT_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONDITION_DOCUMENT_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONDITION_DOCUMENT_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::TRIGGER_CONDITION_DOCUMENT_DELETED)
		) {
			try {
				$schema = $this->schemaLoader->loadByNamespace(
					'schemas/modules/triggers-module',
					'document.condition.json',
				);

				$parsedData = $this->validator->validate($data, $schema);

				$type = MetadataTypes\TriggerConditionType::get($parsedData->offsetGet('type'));
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidArgument('Provided data could not be validated', $ex->getCode(), $ex);
			}

			if ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_DUMMY)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\DummyCondition::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_DEVICE_PROPERTY)) {
				return $this->documentFactory->create(
					MetadataDocuments\TriggersModule\DevicePropertyCondition::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_CHANNEL_PROPERTY)) {
				return $this->documentFactory->create(
					MetadataDocuments\TriggersModule\ChannelPropertyCondition::class,
					$data,
				);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_TIME)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\TimeCondition::class, $data);
			} elseif ($type->equalsValue(MetadataTypes\TriggerConditionType::TYPE_DATE)) {
				return $this->documentFactory->create(MetadataDocuments\TriggersModule\DateCondition::class, $data);
			} else {
				throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported condition type');
			}
		}

		throw new Exceptions\InvalidState('Transformer could not be created');
	}

}
