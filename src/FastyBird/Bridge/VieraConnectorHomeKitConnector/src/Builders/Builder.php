<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Builders
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Builders;

use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Queries;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers as HomeKitHelpers;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Connector\Viera\Entities as VieraEntities;
use FastyBird\Connector\Viera\Exceptions as VieraExceptions;
use FastyBird\Connector\Viera\Queries as VieraQueries;
use FastyBird\Connector\Viera\Types as VieraTypes;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Formats as MetadataFormats;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Throwable;
use TypeError;
use ValueError;
use function array_diff;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtolower;
use function strval;

/**
 * HomeKit device builder
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Builders
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Builder
{

	use Nette\SmartObject;

	public function __construct(
		private readonly VieraConnectorHomeKitConnector\Logger $logger,
		private readonly Mapping\Builder $mappingBuilder,
		private readonly HomeKitHelpers\Loader $loader,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
		private readonly Localization\Translator $translator,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function build(
		VieraEntities\Devices\Device $viera,
		HomeKitEntities\Connectors\Connector $homeKitConnector,
		Entities\Devices\Viera|null $accessory = null,
	): Entities\Devices\Viera
	{
		$updated = null;

		try {
			if ($accessory === null) {
				$findAccessoryQuery = new Queries\Entities\FindVieraDevices();
				$findAccessoryQuery->forParent($viera);

				$accessory = $this->devicesRepository->findOneBy(
					$findAccessoryQuery,
					Entities\Devices\Viera::class,
				);
			}

			$updated = $this->createAccessory(
				$viera,
				$homeKitConnector,
				$accessory,
			);

			$mapping = $this->mappingBuilder->getServicesMapping();

			foreach ($mapping->getServices() as $serviceMapping) {
				$this->createService($viera, $updated, $serviceMapping);
			}
		} catch (Throwable $ex) {
			if ($updated !== null && $accessory === null) {
				$this->devicesManager->delete($updated);
			}

			throw $ex;
		}

		return $updated;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function createAccessory(
		VieraEntities\Devices\Device $viera,
		HomeKitEntities\Connectors\Connector $homeKitConnector,
		Entities\Devices\Viera|null $accessory = null,
	): Entities\Devices\Viera
	{
		try {
			if ($accessory === null) {
				$identifier = $viera->getIdentifier();

				$findDeviceQuery = new HomeKitQueries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($viera->getIdentifier());

				$existing = $this->devicesRepository->findOneBy(
					$findDeviceQuery,
					HomeKitEntities\Devices\Device::class,
				);

				if ($existing !== null) {
					$identifierPattern = $viera->getIdentifier() . '-%d';
					$identifier = null;

					for ($i = 1; $i <= 100; $i++) {
						$findDeviceQuery = new HomeKitQueries\Entities\FindDevices();
						$findDeviceQuery->byIdentifier(sprintf($identifierPattern, $i));

						if (
							$this->devicesRepository->findOneBy(
								$findDeviceQuery,
								HomeKitEntities\Devices\Device::class,
							) === null
						) {
							$identifier = sprintf($identifierPattern, $i);

							break;
						}
					}
				}

				if ($identifier === null) {
					throw new Exceptions\InvalidState('Device identifier could not be calculated');
				}

				$categoryProperty = $modelProperty = $manufacturerProperty = $serialNumberProperty = null;
			} else {
				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::CATEGORY->value);

				$categoryProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$categoryProperty !== null
					&& !$categoryProperty instanceof DevicesEntities\Devices\Properties\Variable
				) {
					$this->devicesPropertiesManager->delete($categoryProperty);

					$categoryProperty = null;
				}

				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::MODEL->value);

				$modelProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if ($modelProperty !== null && !$modelProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->delete($modelProperty);

					$modelProperty = null;
				}

				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value);

				$manufacturerProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$manufacturerProperty !== null
					&& !$manufacturerProperty instanceof DevicesEntities\Devices\Properties\Variable
				) {
					$this->devicesPropertiesManager->delete($manufacturerProperty);

					$manufacturerProperty = null;
				}

				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value);

				$serialNumberProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$serialNumberProperty !== null
					&& !$serialNumberProperty instanceof DevicesEntities\Devices\Properties\Variable
				) {
					$this->devicesPropertiesManager->delete($serialNumberProperty);

					$serialNumberProperty = null;
				}
			}

			$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($viera);
			$findDevicePropertyQuery->byIdentifier(VieraTypes\DevicePropertyIdentifier::MODEL->value);

			$vieraModelProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($viera);
			$findDevicePropertyQuery->byIdentifier(VieraTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value);

			$vieraSerialNumberProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			if ($accessory === null) {
				$accessory = $this->devicesManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Devices\Viera::class,
					'connector' => $homeKitConnector,
					'identifier' => $identifier,
					'parents' => [$viera],
					'name' => $viera->getName(),
				]));
				assert($accessory instanceof Entities\Devices\Viera);
			}

			if ($categoryProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::CATEGORY->value,
					'dataType' => MetadataTypes\DataType::UCHAR,
					'value' => HomeKitTypes\AccessoryCategory::TELEVISION->value,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($categoryProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::UCHAR,
					'value' => HomeKitTypes\AccessoryCategory::TELEVISION->value,
				]));
			}

			if ($modelProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::MODEL->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $vieraModelProperty?->getValue() ?? VieraConnectorHomeKitConnector\Constants::MODEL,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($modelProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $vieraModelProperty?->getValue() ?? VieraConnectorHomeKitConnector\Constants::MODEL,
				]));
			}

			if ($manufacturerProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => VieraConnectorHomeKitConnector\Constants::MANUFACTURER,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($manufacturerProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => VieraConnectorHomeKitConnector\Constants::MANUFACTURER,
				]));
			}

			if ($serialNumberProperty === null && $vieraSerialNumberProperty !== null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $vieraSerialNumberProperty->getValue(),
					'device' => $accessory,
				]));
			} elseif ($serialNumberProperty !== null && $vieraSerialNumberProperty !== null) {
				$this->devicesPropertiesManager->update($serialNumberProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $vieraSerialNumberProperty->getValue(),
				]));
			}

			$this->databaseHelper->commitTransaction();

			$this->logger->debug(
				'Viera accessory was created',
				[
					'source' => MetadataTypes\Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value,
					'type' => 'builder',
					'thermostat' => [
						'id' => $viera->getId()->toString(),
					],
					'accessory' => [
						'id' => $accessory->getId()->toString(),
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('HomeKit device could not be created', $ex->getCode(), $ex);
		}

		return $accessory;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VieraExceptions\InvalidArgument
	 */
	private function createService(
		VieraEntities\Devices\Device $viera,
		Entities\Devices\Viera $accessory,
		Mapping\Services\Service $serviceMapping,
	): bool
	{
		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists($serviceMapping->getType()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				$serviceMapping->getType()->value,
			));
		}

		$serviceMetadata = $metadata->offsetGet($serviceMapping->getType()->value);

		if (
			!$serviceMetadata instanceof Utils\ArrayHash
			|| !$serviceMetadata->offsetExists('UUID')
			|| !is_string($serviceMetadata->offsetGet('UUID'))
			|| !$serviceMetadata->offsetExists('RequiredCharacteristics')
			|| !$serviceMetadata->offsetGet('RequiredCharacteristics') instanceof Utils\ArrayHash
		) {
			throw new Exceptions\InvalidState('Service definition is missing required attributes');
		}

		$serviceIndex = 1;
		$maxServices = 1;
		$serviceNames = null;
		$serviceValues = null;

		if ($serviceMapping instanceof Mapping\Services\InputSource) {
			if ($serviceMapping->getChannel() === null) {
				throw new Exceptions\InvalidState('Viera input source channel mapping is not provided');
			}

			$findChannelQuery = new VieraQueries\Entities\FindChannels();
			$findChannelQuery->forDevice($viera);
			$findChannelQuery->byIdentifier(VieraTypes\ChannelType::from($serviceMapping->getChannel()));

			$channel = $this->channelsRepository->findOneBy(
				$findChannelQuery,
				VieraEntities\Channels\Channel::class,
			);

			if ($channel === null) {
				throw new Exceptions\InvalidState('Viera input source channel could not be loaded');
			}

			$findPropertyQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
			$findPropertyQuery->forChannel($channel);
			$findPropertyQuery->endWithIdentifier(VieraTypes\ChannelPropertyIdentifier::INPUT_SOURCE->value);

			$inputSourceProperty = $this->channelsPropertiesRepository->findOneBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			if ($inputSourceProperty === null) {
				throw new Exceptions\InvalidState('Viera input source channel property could not be loaded');
			}

			if (!$inputSourceProperty->getFormat() instanceof MetadataFormats\CombinedEnum) {
				throw new Exceptions\InvalidState(
					'Viera input source channel property is wrongly configured. This service could not be mapped',
				);
			}

			$maxServices = 0;
			$serviceNames = [];
			$serviceValues = [];

			$nameIndex = $serviceIndex;

			foreach ($inputSourceProperty->getFormat()->getItems() as $item) {
				assert(
					count($item) === 3
					&& $item[0] instanceof MetadataFormats\CombinedEnumItem
					&& is_string($item[0]->getValue())
					&& $item[1] instanceof MetadataFormats\CombinedEnumItem
					&& is_numeric($item[1]->getValue()),
				);

				$serviceNames[$nameIndex] = str_replace('_', ' ', Utils\Strings::firstUpper($item[0]->getValue()));
				$serviceValues[$nameIndex] = intval($item[1]->getValue());

				++$nameIndex;
				++$maxServices;
			}
		}

		do {
			$channel = null;

			if ($serviceMapping->getChannel() !== null) {
				$findChannelQuery = new VieraQueries\Entities\FindChannels();
				$findChannelQuery->forDevice($viera);
				$findChannelQuery->byIdentifier(VieraTypes\ChannelType::from($serviceMapping->getChannel()));

				$channel = $this->channelsRepository->findOneBy(
					$findChannelQuery,
					VieraEntities\Channels\Channel::class,
				);

				if ($channel === null) {
					throw new Exceptions\InvalidState('Viera device channel for mapping property could not be loaded');
				}
			}

			try {
				$identifier = strtolower(
					strval(
						preg_replace(
							'/(?<!^)[A-Z]/',
							'_$0',
							$serviceMapping->getType()->value,
						),
					),
				) . '_' . $serviceIndex;

				$findServiceQuery = new Queries\Entities\FindVieraChannels();
				$findServiceQuery->forDevice($accessory);
				$findServiceQuery->byIdentifier($identifier);

				$service = $this->channelsRepository->findOneBy(
					$findServiceQuery,
					Entities\Channels\Viera::class,
				);

				$name = $this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.misc.services.' . Utils\Strings::lower(
						$serviceMapping->getType()->value,
					),
				);

				if ($serviceMapping->isMultiple()) {
					$name .= ' ' . ($serviceNames !== null && array_key_exists($serviceIndex, $serviceNames)
						? $serviceNames[$serviceIndex]
						: $serviceIndex
					);
				}

				if (
					$serviceMapping instanceof Mapping\Services\InputSource
					&& $serviceNames !== null && array_key_exists($serviceIndex, $serviceNames)
				) {
					$name = $serviceNames[$serviceIndex];
				}

				if ($service === null) {
					$service = $this->databaseHelper->transaction(
						function () use ($identifier, $accessory, $serviceMapping, $name): Entities\Channels\Viera {
							$channel = $this->channelsManager->create(Utils\ArrayHash::from([
								'entity' => $serviceMapping->getClass(),
								'identifier' => $identifier,
								'device' => $accessory,
								'name' => $name,
							]));
							assert($channel instanceof Entities\Channels\Viera);

							return $channel;
						},
					);

					$this->logger->debug(
						'Viera service for viera connector accessory was created',
						[
							'source' => MetadataTypes\Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value,
							'type' => 'builder',
							'viera' => [
								'id' => $viera->getId()->toString(),
							],
							'accessory' => [
								'id' => $accessory->getId()->toString(),
							],
							'service' => [
								'id' => $service->getId()->toString(),
							],
						],
					);
				}
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					sprintf(
						'HomeKit service: %s could not be created',
						$serviceMapping->getType()->value,
					),
					$ex->getCode(),
					$ex,
				);
			}

			$requiredCharacteristics = (array) $serviceMetadata->offsetGet('RequiredCharacteristics');
			$optionalCharacteristics = $virtualCharacteristics = [];

			if (
				$serviceMetadata->offsetExists('OptionalCharacteristics')
				&& $serviceMetadata->offsetGet('OptionalCharacteristics') instanceof Utils\ArrayHash
			) {
				$optionalCharacteristics = (array) $serviceMetadata->offsetGet('OptionalCharacteristics');
			}

			if (
				$serviceMetadata->offsetExists('VirtualCharacteristics')
				&& $serviceMetadata->offsetGet('VirtualCharacteristics') instanceof Utils\ArrayHash
			) {
				$virtualCharacteristics = (array) $serviceMetadata->offsetGet('VirtualCharacteristics');
			}

			$createdCharacteristics = [];

			foreach ($serviceMapping->getCharacteristics() as $characteristicMapping) {
				if (
					!in_array($characteristicMapping->getType()->value, $requiredCharacteristics, true)
					&& !in_array($characteristicMapping->getType()->value, $optionalCharacteristics, true)
					&& !in_array($characteristicMapping->getType()->value, $virtualCharacteristics, true)
				) {
					continue;
				}

				$result = $this->createCharacteristic(
					$viera,
					$channel,
					$service,
					$characteristicMapping,
					is_array($serviceNames) && array_key_exists(
						$serviceIndex,
						$serviceNames,
					) ? $serviceNames[$serviceIndex] : null,
					is_array($serviceValues) && array_key_exists(
						$serviceIndex,
						$serviceValues,
					) ? $serviceValues[$serviceIndex] : null,
				);

				if ($result) {
					$createdCharacteristics[] = $characteristicMapping->getType();
				}
			}

			if (
				array_diff(
					$requiredCharacteristics,
					array_map(
						static fn (HomeKitTypes\CharacteristicType $createdCharacteristic): string => $createdCharacteristic->value,
						$createdCharacteristics,
					),
				) !== []
			) {
				$this->channelsManager->delete($service);

				break;
			}

			++$serviceIndex;
		} while ($serviceIndex < $maxServices);

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VieraExceptions\InvalidArgument
	 */
	private function createCharacteristic(
		VieraEntities\Devices\Device $viera,
		VieraEntities\Channels\Channel|null $channel,
		Entities\Channels\Viera $service,
		Mapping\Characteristics\Characteristic $characteristicMapping,
		string|null $name = null,
		int|null $value = null,
	): bool
	{
		$connectProperty = null;

		if ($characteristicMapping->getProperty() !== null) {
			if ($channel === null && $characteristicMapping->getChannel() === null) {
				throw new Exceptions\InvalidState(
					'Viera device channel mapping is wrongly configured. Channel could not be loaded',
				);
			}

			if ($characteristicMapping->getChannel() !== null) {
				$findChannelQuery = new VieraQueries\Entities\FindChannels();
				$findChannelQuery->forDevice($viera);
				$findChannelQuery->byIdentifier(VieraTypes\ChannelType::from($characteristicMapping->getChannel()));

				$channel = $this->channelsRepository->findOneBy(
					$findChannelQuery,
					VieraEntities\Channels\Channel::class,
				);
			}

			if ($channel === null) {
				throw new Exceptions\InvalidState('Viera device channel for mapping property could not be loaded');
			}

			$findPropertyQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
			$findPropertyQuery->forChannel($channel);
			$findPropertyQuery->endWithIdentifier($characteristicMapping->getProperty());

			$connectProperty = $this->channelsPropertiesRepository->findOneBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			if ($connectProperty === null && !$characteristicMapping->isNullable()) {
				return false;
			}
		}

		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($characteristicMapping->getType()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$characteristicMapping->getType()->value,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($characteristicMapping->getType()->value);

		if (
			!$characteristicMetadata instanceof Utils\ArrayHash
			|| !$characteristicMetadata->offsetExists('Format')
			|| !is_string($characteristicMetadata->offsetGet('Format'))
			|| !$characteristicMetadata->offsetExists('DataType')
			|| (
				!is_string($characteristicMetadata->offsetGet('DataType'))
				&& !$characteristicMetadata->offsetGet('DataType') instanceof Utils\ArrayHash
			)
		) {
			throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
		}

		if ($connectProperty !== null) {
			$entity = DevicesEntities\Channels\Properties\Mapped::class;

			$settable = $connectProperty->isSettable();
			$queryable = $connectProperty->isQueryable();

			$format = $characteristicMapping->getFormat() ?? $this->buildFormat($characteristicMetadata);
			$default = null;
			$dataType = null;

			if (
				(
					$characteristicMapping->getType() === HomeKitTypes\CharacteristicType::ACTIVE_IDENTIFIER
					|| $characteristicMapping->getType() === HomeKitTypes\CharacteristicType::INPUT_SOURCE
				) && $connectProperty->getIdentifier() === VieraTypes\ChannelPropertyIdentifier::INPUT_SOURCE->value
			) {
				$inputSourceFormat = $connectProperty->getFormat();
				assert($inputSourceFormat instanceof MetadataFormats\CombinedEnum);

				$format = array_map(static function (array $items): array {
					assert(
						count($items) === 3
						&& $items[0] instanceof MetadataFormats\CombinedEnumItem
						&& $items[1] instanceof MetadataFormats\CombinedEnumItem,
					);

					return [$items[1]->getValue(), $items[1]->getValue(), $items[0]->getValue()];
				}, $inputSourceFormat->getItems());
			}

			if ($characteristicMapping->getType() === HomeKitTypes\CharacteristicType::POWER_MODE_SELECTION) {
				$format = [
					[
						0, 0, MetadataTypes\Payloads\Button::CLICKED,
					],
					[
						1, 0, MetadataTypes\Payloads\Button::CLICKED,
					],
				];

				$dataType = MetadataTypes\DataType::ENUM;
			}

			if ($characteristicMetadata->offsetExists('Default')) {
				$default = $characteristicMetadata->offsetGet('Default');
			}

			if ($dataType === null) {
				if ($characteristicMetadata->offsetGet('DataType') instanceof Utils\ArrayHash) {
					$dataTypes = array_map(
						static fn (string $type): MetadataTypes\DataType => MetadataTypes\DataType::from($type),
						(array) $characteristicMetadata->offsetGet('DataType'),
					);

					if ($dataTypes === []) {
						throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
					}
				} else {
					$dataTypes = [MetadataTypes\DataType::from($characteristicMetadata->offsetGet('DataType'))];
				}

				if (!in_array($connectProperty->getDataType(), $dataTypes, true) && $format === null) {
					throw new Exceptions\InvalidState(sprintf(
						'Provided Viera property: %s could not be mapped to HomeKit characteristic due to invalid data type',
						$connectProperty->getIdentifier(),
					));
				}

				$dataType = $connectProperty->getDataType();
			}
		} else {
			$entity = DevicesEntities\Channels\Properties\Dynamic::class;

			if (
				$characteristicMapping->getType() === HomeKitTypes\CharacteristicType::NAME
				|| $characteristicMapping->getType() === HomeKitTypes\CharacteristicType::CONFIGURED_NAME
			) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;

				$value = $name ?? ($viera->getName() ?? $viera->getIdentifier());

				if ($service->getServiceType() === HomeKitTypes\ServiceType::TELEVISION_SPEAKER) {
					$value .= ' ' . $this->translator->translate(
						'//viera-connector-homekit-connector-bridge.base.misc.speaker',
					);
				}
			}

			if (
				$service->getServiceType() === HomeKitTypes\ServiceType::INPUT_SOURCE
				&& $characteristicMapping->getType() === HomeKitTypes\CharacteristicType::IDENTIFIER
			) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;
			}

			if (
				$service->getServiceType() === HomeKitTypes\ServiceType::INPUT_SOURCE
				&& $characteristicMapping->getType() === HomeKitTypes\CharacteristicType::INPUT_SOURCE_TYPE
			) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;

				if ($value > 999) {
					$value = 10;
				} elseif ($value < 100) {
					$value = 3;
				} elseif ($value === 500) {
					$value = 2;
				} else {
					$value = null;
				}
			}

			if ($characteristicMapping->getValue() !== null) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;

				$value = $characteristicMapping->getValue();
			}

			$settable = $queryable = false;
			$format = $this->buildFormat($characteristicMetadata);
			$default = null;

			if ($characteristicMetadata->offsetExists('Default')) {
				$default = $characteristicMetadata->offsetGet('Default');
			}

			if (
				$characteristicMetadata->offsetExists('Permissions')
				&& $characteristicMetadata->offsetGet('Permissions') instanceof Utils\ArrayHash
			) {
				$permissions = (array) $characteristicMetadata->offsetGet('Permissions');

				$settable = in_array(HomeKitTypes\CharacteristicPermission::WRITE->value, $permissions, true);
			}

			if ($characteristicMetadata->offsetGet('DataType') instanceof Utils\ArrayHash) {
				$dataTypes = array_map(
					static fn (string $type): MetadataTypes\DataType => MetadataTypes\DataType::from($type),
					(array) $characteristicMetadata->offsetGet('DataType'),
				);

				if ($dataTypes === []) {
					throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
				}

				$dataType = $dataTypes[0];
			} else {
				$dataType = MetadataTypes\DataType::from($characteristicMetadata->offsetGet('DataType'));
			}
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$identifier = strtolower(
				strval(
					preg_replace(
						'/(?<!^)[A-Z]/',
						'_$0',
						$characteristicMapping->getType()->value,
					),
				),
			);

			$findCharacteristic = new DevicesQueries\Entities\FindChannelProperties();
			$findCharacteristic->forChannel($service);
			$findCharacteristic->byIdentifier($identifier);

			$characteristic = $this->channelsPropertiesRepository->findOneBy($findCharacteristic);

			if (
				$characteristic !== null
				&& !$characteristic instanceof $entity
			) {
				$this->channelsPropertiesManager->delete($characteristic);

				$characteristic = null;
			}

			if ($characteristic === null) {
				$characteristic = $this->channelsPropertiesManager->create(
					Utils\ArrayHash::from(array_merge(
						[
							'entity' => $entity,
							'identifier' => $identifier,
							'channel' => $service,
							'dataType' => $dataType,
							'format' => $format,
						],
						$connectProperty !== null
							? [
								'parent' => $connectProperty,
							]
							: [],
						$entity === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
						$entity !== DevicesEntities\Channels\Properties\Mapped::class
							? [
								'default' => $default,
							]
							: [],
					)),
				);

				$this->logger->debug(
					'Characteristic for viera service was created',
					[
						'source' => MetadataTypes\Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'viera' => [
							'id' => $viera->getId()->toString(),
						],
						'accessory' => [
							'id' => $service->getDevice()->getId()->toString(),
						],
						'service' => [
							'id' => $service->getId()->toString(),
						],
						'characteristic' => [
							'id' => $characteristic->getId()->toString(),
							'type' => $characteristicMapping->getType()->value,
						],
					],
				);
			} else {
				$this->channelsPropertiesManager->update(
					$characteristic,
					Utils\ArrayHash::from(array_merge(
						[
							'dataType' => $dataType,
							'format' => $format,
						],
						$connectProperty !== null
							? [
								'parent' => $connectProperty,
							]
							: [],
						$entity === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
						$entity !== DevicesEntities\Channels\Properties\Mapped::class
							? [
								'default' => $default,
							]
							: [],
					)),
				);

				$this->logger->debug(
					'Characteristic for viera service was updated',
					[
						'source' => MetadataTypes\Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'viera' => [
							'id' => $viera->getId()->toString(),
						],
						'accessory' => [
							'id' => $service->getDevice()->getId()->toString(),
						],
						'service' => [
							'id' => $service->getId()->toString(),
						],
						'characteristic' => [
							'id' => $characteristic->getId()->toString(),
							'type' => $characteristicMapping->getType()->value,
						],
					],
				);
			}

			$this->databaseHelper->commitTransaction();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState(
				sprintf(
					'HomeKit characteristic: %s could not be created',
					$characteristicMapping->getType()->value,
				),
				$ex->getCode(),
				$ex,
			);
		}

		return true;
	}

	/**
	 * @return MetadataFormats\StringEnum|array<int, float|null>|null
	 */
	private function buildFormat(Utils\ArrayHash $characteristicMetadata): MetadataFormats\StringEnum|array|null
	{
		$format = null;

		if (
			$characteristicMetadata->offsetExists('ValidValues')
			&& $characteristicMetadata->offsetGet('ValidValues') instanceof Utils\ArrayHash
		) {
			$format = new MetadataFormats\StringEnum(
				array_values((array) $characteristicMetadata->offsetGet('ValidValues')),
			);
		}

		if (
			$characteristicMetadata->offsetExists('MinValue')
			|| $characteristicMetadata->offsetExists('MaxValue')
		) {
			$format = [
				$characteristicMetadata->offsetExists('MinValue')
					? floatval($characteristicMetadata->offsetGet('MinValue'))
					: null,
				$characteristicMetadata->offsetExists('MaxValue')
					? floatval($characteristicMetadata->offsetGet('MaxValue'))
					: null,
			];
		}

		return $format;
	}

}
