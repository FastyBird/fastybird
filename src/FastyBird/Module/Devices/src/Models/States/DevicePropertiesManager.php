<?php declare(strict_types = 1);

/**
 * DevicePropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           23.08.22
 */

namespace FastyBird\Module\Devices\Models\States;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\EventDispatcher as PsrEventDispatcher;
use Ramsey\Uuid;
use Throwable;
use function array_map;
use function array_merge;
use function is_array;
use function strval;

/**
 * Useful device dynamic property state helpers
 *
 * @extends PropertiesManager<MetadataDocuments\DevicesModule\DeviceDynamicProperty, MetadataDocuments\DevicesModule\DeviceMappedProperty | null, States\DeviceProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertiesManager extends PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\States\Devices\Repository $devicePropertyStateRepository,
		private readonly Models\States\Devices\Manager $devicePropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
		Devices\Logger $logger,
		ObjectMapper\Processing\Processor $stateMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct($logger, $stateMapper);
	}

	/**
	 * @throws Exceptions\InvalidActualValue
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidExpectedValue
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function request(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|null $source = null,
	): bool
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION),
					$this->documentFactory->create(
						Utils\ArrayHash::from([
							'action' => MetadataTypes\PropertyAction::GET,
							'device' => $property->getDevice()->toString(),
							'property' => $property->getId()->toString(),
						]),
						MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION),
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested action could not be published for write action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$mappedProperty = null;

			if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
				$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

				if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
					throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
				}

				$mappedProperty = $property;

				$property = $parent;
			}

			try {
				$state = $this->devicePropertyStateRepository->find($property->getId());

			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'Devices states repository is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
					],
				);

				return false;
			}

			if ($state === null) {
				return false;
			}

			$readValue = $this->convertStoredState($property, $mappedProperty, $state, true);
			$getValue = $this->convertStoredState($property, $mappedProperty, $state, false);

			$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityReported(
				$property,
				$readValue,
				$getValue,
			));
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidActualValue
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidExpectedValue
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function publish(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|null $source = null,
	): bool
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = $this->devicePropertyStateRepository->find($property->getId());

		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);

			return false;
		}

		if ($state === null) {
			return false;
		}

		$readValue = $this->convertStoredState($property, $mappedProperty, $state, true);
		$getValue = $this->convertStoredState($property, $mappedProperty, $state, false);

		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED),
					$this->documentFactory->create(
						Utils\ArrayHash::from([
							'id' => $property->getId()->toString(),
							'device' => $property->getDevice()->toString(),
							'read' => $readValue->toArray(),
							'get' => $getValue->toArray(),
							'created_at' => $readValue->getCreatedAt()?->format(DateTimeInterface::ATOM),
							'updated_at' => $readValue->getUpdatedAt()?->format(DateTimeInterface::ATOM),
						]),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED,
						),
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested action could not be published for write action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityReported(
				$property,
				$readValue,
				$getValue,
			));
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function read(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
	): States\DeviceProperty|null
	{
		return $this->loadValue($property, true);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function get(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
	): States\DeviceProperty|null
	{
		return $this->loadValue($property, false);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function write(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		Utils\ArrayHash $data,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|null $source = null,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION),
					$this->documentFactory->create(
						Utils\ArrayHash::from(array_merge(
							[
								'action' => MetadataTypes\PropertyAction::SET,
								'device' => $property->getDevice()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'write' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\CoverPayload|MetadataTypes\SwitchPayload|null $item): bool|int|float|string|null => MetadataUtilities\Value::flattenValue(
										$item,
									),
									(array) $data,
								),
							],
						)),
						MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION),
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested value could not be published for write action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$this->saveValue($property, $data, true);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function set(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		Utils\ArrayHash $data,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|null $source = null,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION),
					$this->documentFactory->create(
						Utils\ArrayHash::from(array_merge(
							[
								'action' => MetadataTypes\PropertyAction::SET,
								'device' => $property->getDevice()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'set' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\CoverPayload|MetadataTypes\SwitchPayload|null $item): bool|int|float|string|null => MetadataUtilities\Value::flattenValue(
										$item,
									),
									(array) $data,
								),
							],
						)),
						MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION),
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested value could not be published for set action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$this->saveValue($property, $data, false);
		}
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->set(
					$item,
					Utils\ArrayHash::from([
						States\Property::VALID_FIELD => $state,
					]),
				);
			}
		} else {
			$this->set(
				$property,
				Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]),
			);
		}
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setPendingState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|array $property,
		bool $pending,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				if ($pending === false) {
					$this->set(
						$item,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => null,
							States\Property::PENDING_FIELD => false,
						]),
					);
				} else {
					$this->set(
						$item,
						Utils\ArrayHash::from([
							States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
					);
				}
			}
		} else {
			if ($pending === false) {
				$this->set(
					$property,
					Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]),
				);
			} else {
				$this->set(
					$property,
					Utils\ArrayHash::from([
						States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
							DateTimeInterface::ATOM,
						),
					]),
				);
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function delete(Uuid\UuidInterface $id): bool
	{
		try {
			$result = $this->devicePropertiesStatesManager->delete($id);

			if ($result) {
				$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted($id));

				foreach ($this->findChildren($id) as $child) {
					$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted($child->getId()));
				}
			}

			return $result;
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);
		}

		return false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @interal
	 */
	public function loadValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		bool $forReading,
	): States\DeviceProperty|null
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = $this->devicePropertyStateRepository->find($property->getId());

		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);

			return null;
		}

		try {
			if ($state === null) {
				return null;
			}

			return $this->convertStoredState(
				$property,
				$mappedProperty,
				$state,
				$forReading,
			);
		} catch (Exceptions\InvalidActualValue $ex) {
			try {
				$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]));

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				return $this->loadValue($property, $forReading);
			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'Devices states manager is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
					],
				);

				return null;
			}
		} catch (Exceptions\InvalidExpectedValue $ex) {
			try {
				$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				return $this->loadValue($property, $forReading);
			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'Devices states manager is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
					],
				);

				return null;
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @interal
	 */
	public function saveValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): void
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		if ($mappedProperty !== null && $forWriting === false) {
			throw new Exceptions\InvalidArgument('Mapped property could not be stored as from device');
		}

		try {
			$state = $this->devicePropertyStateRepository->find($property->getId());
		} catch (Exceptions\NotImplemented) {
			$state = null;
		}

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			try {
				if (
					$property->getInvalid() !== null
					&& strval(
						MetadataUtilities\Value::flattenValue(
							/** @phpstan-ignore-next-line */
							$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						),
					) === strval(
						MetadataUtilities\Value::flattenValue($property->getInvalid()),
					)
				) {
					$data->offsetSet(States\Property::ACTUAL_VALUE_FIELD, null);
					$data->offsetSet(States\Property::VALID_FIELD, false);

				} else {
					$actualValue = $this->convertWriteActualValue(
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						$property,
					);

					$data->offsetSet(
						States\Property::ACTUAL_VALUE_FIELD,
						MetadataUtilities\Value::flattenValue($actualValue),
					);
					$data->offsetSet(States\Property::VALID_FIELD, true);
				}
			} catch (MetadataExceptions\InvalidValue $ex) {
				$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)) {
			if (
				$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) !== null
				&& $data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) !== ''
			) {
				try {
					$expectedValue = $this->convertWriteExpectedValue(
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$property,
						$mappedProperty,
						$forWriting,
					);

					if (
						$expectedValue !== null
						&& (
							!$property->isSettable()
							|| (
								$mappedProperty !== null
								&& !$mappedProperty->isSettable()
							)
						)
					) {
						throw new Exceptions\InvalidArgument(
							'Property is not settable, expected value could not written',
						);
					}

					$data->offsetSet(
						States\Property::EXPECTED_VALUE_FIELD,
						MetadataUtilities\Value::flattenValue($expectedValue),
					);
					$data->offsetSet(
						States\Property::PENDING_FIELD,
						$expectedValue !== null,
					);
				} catch (MetadataExceptions\InvalidValue $ex) {
					$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
					$data->offsetSet(States\Property::PENDING_FIELD, false);

					$this->logger->error(
						'Provided property expected value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::DEVICES,
							'type' => 'device-properties-states',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
						],
					);
				}
			} else {
				$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
				$data->offsetSet(States\Property::PENDING_FIELD, false);
			}
		}

		if ($data->count() === 0) {
			return;
		}

		if (
			$state !== null
			&& (
				(
					$data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)
					&& MetadataUtilities\Value::flattenValue($state->getActualValue()) === $data->offsetGet(
						States\Property::EXPECTED_VALUE_FIELD,
					)
				) || (
					$data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)
					&& MetadataUtilities\Value::flattenValue($state->getExpectedValue()) === $data->offsetGet(
						States\Property::ACTUAL_VALUE_FIELD,
					)
				)
			)
		) {
			$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
			$data->offsetSet(States\Property::PENDING_FIELD, false);
		}

		try {
			if ($state === null) {
				$result = $this->devicePropertiesStatesManager->create(
					$property,
					$data,
				);

			} else {
				$result = $this->devicePropertiesStatesManager->update(
					$property,
					$state,
					$data,
				);

				if ($result === false) {
					return;
				}
			}

			$readValue = $this->convertStoredState($property, null, $result, true);
			$getValue = $this->convertStoredState($property, null, $result, false);

			if ($state === null) {
				$this->dispatcher?->dispatch(
					new Events\DevicePropertyStateEntityCreated($property, $readValue, $getValue),
				);
			} else {
				$this->dispatcher?->dispatch(
					new Events\DevicePropertyStateEntityUpdated($property, $readValue, $getValue),
				);
			}

			foreach ($this->findChildren($property->getId()) as $child) {
				$readValue = $this->convertStoredState($property, $child, $result, true);
				$getValue = $this->convertStoredState($property, $child, $result, false);

				if ($state === null) {
					$this->dispatcher?->dispatch(
						new Events\DevicePropertyStateEntityCreated($child, $readValue, $getValue),
					);
				} else {
					$this->dispatcher?->dispatch(
						new Events\DevicePropertyStateEntityUpdated($child, $readValue, $getValue),
					);
				}
			}

			$this->logger->debug(
				$state === null ? 'Device property state was created' : 'Device property state was updated',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
					'property' => [
						'id' => $property->getId()->toString(),
						'state' => $result->toArray(),
					],
				],
			);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);
		}
	}

	/**
	 * @return array<MetadataDocuments\DevicesModule\DeviceMappedProperty>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function findChildren(Uuid\UuidInterface $id): array
	{
		$findPropertiesQuery = new Queries\Configuration\FindDeviceMappedProperties();
		$findPropertiesQuery->byParentId($id);

		return $this->devicePropertiesConfigurationRepository->findAllBy(
			$findPropertiesQuery,
			MetadataDocuments\DevicesModule\DeviceMappedProperty::class,
		);
	}

}
