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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use IPub\Phone\Exceptions as PhoneExceptions;

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
		private readonly MetadataEntities\Actions\ActionConnectorControlEntityFactory $actionConnectorControlEntityFactory,
		private readonly MetadataEntities\Actions\ActionConnectorPropertyEntityFactory $actionConnectorPropertyEntityFactory,
		private readonly MetadataEntities\Actions\ActionDeviceControlEntityFactory $actionDeviceControlEntityFactory,
		private readonly MetadataEntities\Actions\ActionDevicePropertyEntityFactory $actionDevicePropertyEntityFactory,
		private readonly MetadataEntities\Actions\ActionChannelControlEntityFactory $actionChannelControlEntityFactory,
		private readonly MetadataEntities\Actions\ActionChannelPropertyEntityFactory $actionChannelPropertyEntityFactory,
		private readonly MetadataEntities\Actions\ActionTriggerControlEntityFactory $actionTriggerControlEntityFactory,
		private readonly MetadataEntities\AccountsModule\AccountEntityFactory $accountEntityFactory,
		private readonly MetadataEntities\AccountsModule\EmailEntityFactory $emailEntityFactory,
		private readonly MetadataEntities\AccountsModule\IdentityEntityFactory $identityEntityFactory,
		private readonly MetadataEntities\AccountsModule\RoleEntityFactory $roleEntityFactory,
		private readonly MetadataEntities\TriggersModule\ActionEntityFactory $triggerActionEntityFactory,
		private readonly MetadataEntities\TriggersModule\ConditionEntityFactory $triggerConditionEntityFactory,
		private readonly MetadataEntities\TriggersModule\NotificationEntityFactory $triggerNotificationEntityFactory,
		private readonly MetadataEntities\TriggersModule\TriggerControlEntityFactory $triggerControlEntityFactory,
		private readonly MetadataEntities\TriggersModule\TriggerEntityFactory $triggerEntityFactory,
		private readonly MetadataEntities\DevicesModule\ConnectorEntityFactory $connectorEntityFactory,
		private readonly MetadataEntities\DevicesModule\ConnectorControlEntityFactory $connectorControlEntityFactory,
		private readonly MetadataEntities\DevicesModule\ConnectorPropertyEntityFactory $connectorPropertyEntityFactory,
		private readonly MetadataEntities\DevicesModule\DeviceEntityFactory $deviceEntityFactory,
		private readonly MetadataEntities\DevicesModule\DeviceControlEntityFactory $deviceControlEntityFactory,
		private readonly MetadataEntities\DevicesModule\DevicePropertyEntityFactory $devicePropertyEntityFactory,
		private readonly MetadataEntities\DevicesModule\ChannelEntityFactory $channelEntityFactory,
		private readonly MetadataEntities\DevicesModule\ChannelControlEntityFactory $channelControlEntityFactory,
		private readonly MetadataEntities\DevicesModule\ChannelPropertyEntityFactory $channelPropertyEntityFactory,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function create(string $data, MetadataTypes\RoutingKey $routingKey): MetadataEntities\Entity
	{
		// ACTIONS
		if ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ACTION)) {
			return $this->actionConnectorControlEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ACTION)) {
			return $this->actionConnectorPropertyEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ACTION)) {
			return $this->actionDeviceControlEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ACTION)) {
			return $this->actionDevicePropertyEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ACTION)) {
			return $this->actionChannelControlEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION)) {
			return $this->actionChannelPropertyEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ACTION)) {
			return $this->actionTriggerControlEntityFactory->create($data);

			// ACCOUNTS MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ACCOUNT_ENTITY_DELETED)
		) {
			return $this->accountEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_EMAIL_ENTITY_DELETED)
		) {
			return $this->emailEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_IDENTITY_ENTITY_DELETED)
		) {
			return $this->identityEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_ROLE_ENTITY_DELETED)
		) {
			return $this->roleEntityFactory->create($data);

			// DEVICES MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_DELETED)
		) {
			return $this->deviceEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_DELETED)
		) {
			return $this->devicePropertyEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_DELETED)
		) {
			return $this->deviceControlEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_DELETED)
		) {
			return $this->channelEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_DELETED)
		) {
			return $this->channelPropertyEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_DELETED)
		) {
			return $this->channelControlEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_ENTITY_DELETED)
		) {
			return $this->connectorEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_DELETED)
		) {
			return $this->connectorPropertyEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_DELETED)
		) {
			return $this->connectorControlEntityFactory->create($data);

			// TRIGGERS MODULE
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ENTITY_DELETED)
		) {
			return $this->triggerEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_DELETED)
		) {
			return $this->triggerControlEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_DELETED)
		) {
			return $this->triggerActionEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_DELETED)
		) {
			return $this->triggerNotificationEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_CREATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_DELETED)
		) {
			return $this->triggerConditionEntityFactory->create($data);
		}

		throw new Exceptions\InvalidState('Transformer could not be created');
	}

}
