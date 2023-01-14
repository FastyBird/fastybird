<?php declare(strict_types = 1);

/**
 * Gen2.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Nette;
use Nette\Utils;
use RuntimeException;
use function array_key_exists;
use function preg_match;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 2 devices API helpers
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read MetadataSchemas\Validator $schemaValidator
 * @property-read EntityFactory $entityFactory
 */
trait Gen2
{

	private static string $PROPERTY_COMPONENT = '/^(?P<component>[a-zA-Z]+)_(?P<identifier>[0-9]+)(_(?P<attribute>[a-zA-Z0-9]+))?$/';

	private static string $COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	private static string $SWITCH_SET_METHOD = 'Switch.Set';

	private static string $COVER_GO_TO_POSITION_METHOD = 'Cover.GoToPosition';

	private static string $LIGHT_SET_METHOD = 'Light.Set';

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentMethod(string $component): string
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('component', $componentMatches)
			|| !array_key_exists('identifier', $componentMatches)
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::TYPE_SWITCH
			&& $componentMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_ON
		) {
			return self::$SWITCH_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::TYPE_COVER
			&& $componentMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_POSITION
		) {
			return self::$COVER_GO_TO_POSITION_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::TYPE_LIGHT
			&& (
				$componentMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_ON
				|| $componentMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_BRIGHTNESS
			)
		) {
			return self::$LIGHT_SET_METHOD;
		}

		throw new Exceptions\InvalidState('Property method could not be build');
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceStatusResponse(
		string $payload,
		string $schemaFilename,
	): Entities\API\Gen2\DeviceStatus
	{
		$parsedMessage = $this->schemaValidator->validate(
			$payload,
			$this->getSchemaFilePath($schemaFilename),
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = [];
		$ethernet = $wifi = null;

		foreach ($parsedMessage as $key => $status) {
			if (
				$status instanceof Utils\ArrayHash
				&& preg_match(self::$COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::TYPE_SWITCH) {
					$switches[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceSwitchStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_COVER) {
					$covers[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceCoverStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_LIGHT) {
					$lights[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceLightStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_INPUT) {
					$inputs[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceInputStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_TEMPERATURE) {
					$temperature[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceTemperatureStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_HUMIDITY) {
					$humidity[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceHumidityStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_ETHERNET) {
					$ethernet = $this->entityFactory->build(
						Entities\API\Gen2\EthernetStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_WIFI) {
					$wifi = $this->entityFactory->build(
						Entities\API\Gen2\WifiStatus::class,
						$status,
					);
				}
			}
		}

		return new Entities\API\Gen2\DeviceStatus(
			$switches,
			$covers,
			$inputs,
			$lights,
			$temperature,
			$humidity,
			$ethernet,
			$wifi,
		);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceConfigurationResponse(
		string $payload,
		string $schemaFilename,
	): Entities\API\Gen2\DeviceConfiguration
	{
		$parsedMessage = $this->schemaValidator->validate(
			$payload,
			$this->getSchemaFilePath($schemaFilename),
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = [];

		foreach ($parsedMessage as $key => $configuration) {
			if (
				$configuration instanceof Utils\ArrayHash
				&& preg_match(self::$COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::TYPE_SWITCH) {
					$switches[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceSwitchConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_COVER) {
					$covers[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceCoverConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_LIGHT) {
					$lights[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceLightConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_INPUT) {
					$inputs[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceInputConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_TEMPERATURE) {
					$temperature[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceTemperatureConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_HUMIDITY) {
					$humidity[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceHumidityConfiguration::class,
						$configuration,
					);
				}
			}
		}

		return new Entities\API\Gen2\DeviceConfiguration(
			$switches,
			$covers,
			$inputs,
			$lights,
			$temperature,
			$humidity,
		);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceInformationResponse(
		string $payload,
		string $schemaFilename,
	): Entities\API\Gen2\DeviceInformation
	{
		$parsedMessage = $this->schemaValidator->validate(
			$payload,
			$this->getSchemaFilePath($schemaFilename),
		);

		return $this->entityFactory->build(
			Entities\API\Gen2\DeviceInformation::class,
			$parsedMessage,
		);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceEventResponse(
		string $payload,
		string $schemaFilename,
	): Entities\API\Gen2\DeviceEvent
	{
		$parsedMessage = $this->schemaValidator->validate(
			$payload,
			$this->getSchemaFilePath($schemaFilename),
		);

		$events = [];

		foreach ((array) $parsedMessage->offsetGet('events') as $event) {
			if ($event instanceof Utils\ArrayHash) {
				$events[] = $this->entityFactory->build(
					Entities\API\Gen2\ComponentEvent::class,
					$event,
				);
			}
		}

		return new Entities\API\Gen2\DeviceEvent($events);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getSchemaFilePath(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\InvalidState('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
