<?php declare(strict_types = 1);

/**
 * RoutingFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.44.0
 *
 * @date           13.06.22
 */

namespace FastyBird\Library\Metadata\Entities;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use IPub\Phone\Exceptions as PhoneExceptions;

/**
 * Exchange entity factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RoutingFactory
{

	public function __construct(
		private readonly Entities\Actions\ActionConnectorControlEntityFactory $actionConnectorControlEntityFactory,
		private readonly Entities\Actions\ActionConnectorPropertyEntityFactory $actionConnectorPropertyEntityFactory,
		private readonly Entities\Actions\ActionDeviceControlEntityFactory $actionDeviceControlEntityFactory,
		private readonly Entities\Actions\ActionDevicePropertyEntityFactory $actionDevicePropertyEntityFactory,
		private readonly Entities\Actions\ActionChannelControlEntityFactory $actionChannelControlEntityFactory,
		private readonly Entities\Actions\ActionChannelPropertyEntityFactory $actionChannelPropertyEntityFactory,
		private readonly Entities\Actions\ActionTriggerControlEntityFactory $actionTriggerControlEntityFactory,
		private readonly Entities\AccountsModule\AccountEntityFactory $accountEntityFactory,
		private readonly Entities\AccountsModule\EmailEntityFactory $emailEntityFactory,
		private readonly Entities\AccountsModule\IdentityEntityFactory $identityEntityFactory,
		private readonly Entities\AccountsModule\RoleEntityFactory $roleEntityFactory,
		private readonly Entities\TriggersModule\ActionEntityFactory $triggerActionEntityFactory,
		private readonly Entities\TriggersModule\ConditionEntityFactory $triggerConditionEntityFactory,
		private readonly Entities\TriggersModule\NotificationEntityFactory $triggerNotificationEntityFactory,
		private readonly Entities\TriggersModule\TriggerControlEntityFactory $triggerControlEntityFactory,
		private readonly Entities\TriggersModule\TriggerEntityFactory $triggerEntityFactory,
		private readonly Entities\DevicesModule\ConnectorEntityFactory $connectorEntityFactory,
		private readonly Entities\DevicesModule\ConnectorControlEntityFactory $connectorControlEntityFactory,
		private readonly Entities\DevicesModule\ConnectorPropertyEntityFactory $connectorPropertyEntityFactory,
		private readonly Entities\DevicesModule\DeviceEntityFactory $deviceEntityFactory,
		private readonly Entities\DevicesModule\DeviceControlEntityFactory $deviceControlEntityFactory,
		private readonly Entities\DevicesModule\DevicePropertyEntityFactory $devicePropertyEntityFactory,
		private readonly Entities\DevicesModule\DeviceAttributeEntityFactory $deviceAttributeEntityFactory,
		private readonly Entities\DevicesModule\ChannelEntityFactory $channelEntityFactory,
		private readonly Entities\DevicesModule\ChannelControlEntityFactory $channelControlEntityFactory,
		private readonly Entities\DevicesModule\ChannelPropertyEntityFactory $channelPropertyEntityFactory,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 */
	public function create(string $data, Types\RoutingKey $routingKey): Entities\Entity
	{
		// ACTIONS
		if ($routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_CONTROL_ACTION)) {
			return $this->actionConnectorControlEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ACTION)) {
			return $this->actionConnectorPropertyEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_CONTROL_ACTION)) {
			return $this->actionDeviceControlEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ACTION)) {
			return $this->actionDevicePropertyEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_CONTROL_ACTION)) {
			return $this->actionChannelControlEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION)) {
			return $this->actionChannelPropertyEntityFactory->create($data);
		} elseif ($routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONTROL_ACTION)) {
			return $this->actionTriggerControlEntityFactory->create($data);

			// ACCOUNTS MODULE
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_ACCOUNT_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_ACCOUNT_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_ACCOUNT_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_ACCOUNT_ENTITY_DELETED)
		) {
			return $this->accountEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_EMAIL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_EMAIL_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_EMAIL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_EMAIL_ENTITY_DELETED)
		) {
			return $this->emailEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_IDENTITY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_IDENTITY_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_IDENTITY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_IDENTITY_ENTITY_DELETED)
		) {
			return $this->identityEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_ROLE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_ROLE_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_ROLE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_ROLE_ENTITY_DELETED)
		) {
			return $this->roleEntityFactory->create($data);

			// DEVICES MODULE
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ENTITY_DELETED)
		) {
			return $this->deviceEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_DELETED)
		) {
			return $this->devicePropertyEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_CONTROL_ENTITY_DELETED)
		) {
			return $this->deviceControlEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ATTRIBUTE_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ATTRIBUTE_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ATTRIBUTE_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_DEVICE_ATTRIBUTE_ENTITY_DELETED)
		) {
			return $this->deviceAttributeEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_ENTITY_DELETED)
		) {
			return $this->channelEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_DELETED)
		) {
			return $this->channelPropertyEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CHANNEL_CONTROL_ENTITY_DELETED)
		) {
			return $this->channelControlEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_ENTITY_DELETED)
		) {
			return $this->connectorEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_DELETED)
		) {
			return $this->connectorPropertyEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_CONNECTOR_CONTROL_ENTITY_DELETED)
		) {
			return $this->connectorControlEntityFactory->create($data);

			// TRIGGERS MODULE
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ENTITY_DELETED)
		) {
			return $this->triggerEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONTROL_ENTITY_DELETED)
		) {
			return $this->triggerControlEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_ACTION_ENTITY_DELETED)
		) {
			return $this->triggerActionEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_NOTIFICATION_ENTITY_DELETED)
		) {
			return $this->triggerNotificationEntityFactory->create($data);
		} elseif (
			$routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_REPORTED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_CREATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED)
			|| $routingKey->equalsValue(Types\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_DELETED)
		) {
			return $this->triggerConditionEntityFactory->create($data);
		}

		throw new Exceptions\InvalidState('Entity could not be created');
	}

}
