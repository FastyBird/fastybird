<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           16.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Models;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use TypeError;
use ValueError;
use function boolval;
use function floatval;
use function intval;
use function is_bool;
use function is_float;
use function is_int;
use function strval;

/**
 * Third-party device & sub-device property to state mapper
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Helpers\Channels\Channel $channelHelper
 * @property-read Models\StateRepository $stateRepository
 * @property-read DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository
 */
trait StateWriter
{

	/**
	 * @return array<mixed>|null
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function mapChannelToState(
		Documents\Channels\Channel $channel,
		DevicesDocuments\Channels\Properties\Property $propertyToUpdate,
		Queue\Messages\State|null $writeState,
	): array|null
	{
		switch ($this->channelHelper->getCapability($channel)) {
			case Types\Capability::POWER:
				$property = $this->findProtocolProperty($channel, Types\Protocol::POWER_STATE);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (Types\Payloads\PowerPayload::tryFrom(strval($value)) === null) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::POWER_STATE->value => $value,
					],
				];
			case Types\Capability::TOGGLE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::TOGGLE_STATE);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (Types\Payloads\TogglePayload::tryFrom(strval($value)) === null) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						$channel->getIdentifier() => [
							Types\Protocol::TOGGLE_STATE->value => $value,
						],
					],
				];
			case Types\Capability::BRIGHTNESS:
				$property = $this->findProtocolProperty($channel, Types\Protocol::BRIGHTNESS);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_int($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::BRIGHTNESS->value => intval($value),
					],
				];
			case Types\Capability::COLOR_TEMPERATURE:
				$property = $this->findProtocolProperty(
					$channel,
					Types\Protocol::COLOR_TEMPERATURE,
				);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_int($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::COLOR_TEMPERATURE->value => intval($value),
					],
				];
			case Types\Capability::COLOR_RGB:
				$propertyRed = $this->findProtocolProperty(
					$channel,
					Types\Protocol::COLOR_RED,
				);

				if ($propertyRed === null) {
					return null;
				}

				$red = $propertyToUpdate->getId()->equals($propertyRed->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($propertyRed);

				$propertyGreen = $this->findProtocolProperty(
					$channel,
					Types\Protocol::COLOR_GREEN,
				);

				if ($propertyGreen === null) {
					return null;
				}

				$green = $propertyToUpdate->getId()->equals($propertyGreen->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($propertyGreen);

				$propertyBlue = $this->findProtocolProperty(
					$channel,
					Types\Protocol::COLOR_BLUE,
				);

				if ($propertyBlue === null) {
					return null;
				}

				$blue = $propertyToUpdate->getId()->equals($propertyBlue->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($propertyBlue);

				if (
					$red === null || $propertyRed->getInvalid() === null
					|| $green === null || $propertyGreen->getInvalid() === null
					|| $blue === null || $propertyBlue->getInvalid() === null
				) {
					return null;
				}

				if (!is_int($red)) {
					$red = $propertyRed->getInvalid();
				}

				if (!is_int($green)) {
					$green = $propertyGreen->getInvalid();
				}

				if (!is_int($blue)) {
					$blue = $propertyBlue->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::COLOR_RED->value => intval($red),
						Types\Protocol::COLOR_GREEN->value => intval($green),
						Types\Protocol::COLOR_BLUE->value => intval($blue),
					],
				];
			case Types\Capability::PERCENTAGE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::PERCENTAGE);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_int($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::PERCENTAGE->value => intval($value),
					],
				];
			case Types\Capability::MOTOR_CONTROL:
				$property = $this->findProtocolProperty($channel, Types\Protocol::MOTOR_CONTROL);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (Types\Payloads\MotorControlPayload::tryFrom(strval($value)) === null) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::MOTOR_CONTROL->value => $value,
					],
				];
			case Types\Capability::MOTOR_REVERSE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::MOTOR_REVERSE);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_bool($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::MOTOR_REVERSE->value => boolval($value),
					],
				];
			case Types\Capability::MOTOR_CALIBRATION:
				$property = $this->findProtocolProperty(
					$channel,
					Types\Protocol::MOTOR_CALIBRATION,
				);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (Types\Payloads\MotorCalibrationPayload::tryFrom(strval($value)) === null) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::MOTOR_CALIBRATION->value => $value,
					],
				];
			case Types\Capability::STARTUP:
				$property = $this->findProtocolProperty($channel, Types\Protocol::STARTUP);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (Types\Payloads\StartupPayload::tryFrom(strval($value)) === null) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::STARTUP->value => $value,
					],
				];
			case Types\Capability::CAMERA_STREAM:
				$property = $this->findProtocolProperty($channel, Types\Protocol::STREAM_URL);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::CONFIGURATION->value => [
							Types\Protocol::STREAM_URL->value => strval($value),
						],
					],
				];
			case Types\Capability::DETECT:
				$property = $this->findProtocolProperty($channel, Types\Protocol::DETECT);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_bool($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::DETECT->value => boolval($value),
					],
				];
			case Types\Capability::HUMIDITY:
				$property = $this->findProtocolProperty($channel, Types\Protocol::HUMIDITY);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_int($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::HUMIDITY->value => intval($value),
					],
				];
			case Types\Capability::TEMPERATURE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::TEMPERATURE);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_float($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::TEMPERATURE->value => floatval($value),
					],
				];
			case Types\Capability::BATTERY:
				$property = $this->findProtocolProperty($channel, Types\Protocol::BATTERY);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_int($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::BATTERY->value => intval($value),
					],
				];
			case Types\Capability::PRESS:
				$property = $this->findProtocolProperty($channel, Types\Protocol::PRESS);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || Types\Payloads\PressPayload::tryFrom(strval($value)) === null) {
					return null;
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::PRESS->value => $value,
					],
				];
			case Types\Capability::RSSI:
				$property = $this->findProtocolProperty($channel, Types\Protocol::RSSI);

				if ($property === null) {
					return null;
				}

				$value = $propertyToUpdate->getId()->equals($property->getId())
					? MetadataUtilities\Value::flattenValue($writeState?->getExpectedValue())
					: $this->getPropertyValue($property);

				if ($value === null || $property->getInvalid() === null) {
					return null;
				}

				if (!is_int($value)) {
					$value = $property->getInvalid();
				}

				return [
					$this->channelHelper->getCapability($channel)->value => [
						Types\Protocol::RSSI->value => intval($value),
					],
				];
			default:
				throw new Exceptions\InvalidArgument('Provided property type is not supported');
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getPropertyValue(
		DevicesDocuments\Channels\Properties\Property $property,
	): string|int|float|bool|null
	{
		try {
			if (
				$property instanceof DevicesDocuments\Channels\Properties\Dynamic
				|| $property instanceof DevicesDocuments\Channels\Properties\Mapped
			) {
				$value = $this->stateRepository->get($property->getId());
			} elseif ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
				$value = $property->getValue();
			} else {
				throw new Exceptions\InvalidArgument('Provided property is not valid');
			}
		} catch (Exceptions\MissingValue) {
			return null;
		}

		return MetadataUtilities\Value::flattenValue($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findProtocolProperty(
		Documents\Channels\Channel $channel,
		Types\Protocol $protocol,
	): DevicesDocuments\Channels\Properties\Property|null
	{
		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($protocol));

		return $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);
	}

}
