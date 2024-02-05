<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesStates.php
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
 * Useful connector dynamic property state helpers
 *
 * @extends PropertiesManager<MetadataDocuments\DevicesModule\ConnectorDynamicProperty, null, States\ConnectorProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesManager extends PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Connectors\Repository $connectorPropertyStateRepository,
		private readonly Connectors\Manager $connectorPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
		private readonly ExchangePublisher\Publisher $publisher,
		Devices\Logger $logger,
		ObjectMapper\Processing\Processor $stateMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct($logger, $stateMapper);
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		MetadataTypes\Sources\Source|null $source,
	): bool|MetadataDocuments\DevicesModule\ConnectorPropertyState|null
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION),
					$this->documentFactory->create(
						MetadataDocuments\Actions\ActionConnectorProperty::class,
						[
							'action' => MetadataTypes\PropertyAction::GET,
							'connector' => $property->getConnector()->toString(),
							'property' => $property->getId()->toString(),
						],
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
			return $this->readState($property);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function write(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION),
					$this->documentFactory->create(
						MetadataDocuments\Actions\ActionConnectorProperty::class,
						array_merge(
							[
								'action' => MetadataTypes\PropertyAction::SET,
								'connector' => $property->getConnector()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'write' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $item): bool|int|float|string|null => MetadataUtilities\Value::flattenValue(
										$item,
									),
									(array) $data,
								),
							],
						),
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
			$this->writeState($property, $data, true, $source);
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
					MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION),
					$this->documentFactory->create(
						MetadataDocuments\Actions\ActionConnectorProperty::class,
						array_merge(
							[
								'action' => MetadataTypes\PropertyAction::SET,
								'connector' => $property->getConnector()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'set' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $item): bool|int|float|string|null => MetadataUtilities\Value::flattenValue(
										$item,
									),
									(array) $data,
								),
							],
						),
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
			$this->writeState($property, $data, false, $source);
		}
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array<MetadataDocuments\DevicesModule\ConnectorDynamicProperty> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array $property,
		bool $state,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->set(
					$item,
					Utils\ArrayHash::from([
						States\Property::VALID_FIELD => $state,
					]),
					$source,
				);
			}
		} else {
			$this->set(
				$property,
				Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]),
				$source,
			);
		}
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array<MetadataDocuments\DevicesModule\ConnectorDynamicProperty> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setPendingState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array $property,
		bool $pending,
		MetadataTypes\Sources\Source|null $source,
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
						$source,
					);
				} else {
					$this->set(
						$item,
						Utils\ArrayHash::from([
							States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
						$source,
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
					$source,
				);
			} else {
				$this->set(
					$property,
					Utils\ArrayHash::from([
						States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
							DateTimeInterface::ATOM,
						),
					]),
					$source,
				);
			}
		}
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		try {
			$result = $this->connectorPropertiesStatesManager->delete($id);

			if ($result) {
				$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityDeleted(
					$id,
					MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
				));
			}

			return $result;
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES,
					'type' => 'connector-properties-states',
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
	public function readState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
	): MetadataDocuments\DevicesModule\ConnectorPropertyState|null
	{
		try {
			$state = $this->connectorPropertyStateRepository->find($property->getId());

		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES,
					'type' => 'connector-properties-states',
				],
			);

			return null;
		}

		try {
			if ($state === null) {
				return null;
			}

			$readValue = $this->convertStoredState($property, null, $state, true);
			$getValue = $this->convertStoredState($property, null, $state, false);

			return $this->documentFactory->create(
				MetadataDocuments\DevicesModule\ConnectorPropertyState::class,
				[
					'id' => $property->getId()->toString(),
					'connector' => $property->getConnector()->toString(),
					'read' => $readValue->toArray(),
					'get' => $getValue->toArray(),
					'valid' => $state->isValid(),
					'pending' => $state->getPending() instanceof DateTimeInterface
						? $state->getPending()->format(DateTimeInterface::ATOM)
						: $state->getPending(),
					'created_at' => $readValue->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readValue->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);
		} catch (Exceptions\InvalidActualValue $ex) {
			try {
				$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]));

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES,
						'type' => 'connector-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'connectors states manager is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES,
						'type' => 'connector-properties-states',
					],
				);

				return null;
			}
		} catch (Exceptions\InvalidExpectedValue $ex) {
			try {
				$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES,
						'type' => 'connector-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'connectors states manager is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES,
						'type' => 'connector-properties-states',
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
	public function writeState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		Utils\ArrayHash $data,
		bool $forWriting,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		try {
			$state = $this->connectorPropertyStateRepository->find($property->getId());
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
						'source' => MetadataTypes\Sources\Module::DEVICES,
						'type' => 'connector-properties-states',
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
						null,
						$forWriting,
					);

					if ($expectedValue !== null && !$property->isSettable()) {
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
							'source' => MetadataTypes\Sources\Module::DEVICES,
							'type' => 'connector-properties-states',
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

		try {
			if ($state !== null) {
				$actualValue = MetadataUtilities\Value::flattenValue(
					$this->convertReadValue($state->getActualValue(), $property, null, false),
				);
				$expectedValue = MetadataUtilities\Value::flattenValue(
					$this->convertWriteExpectedValue($state->getExpectedValue(), $property, null, false),
				);

				if (
					(
						$data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)
						&& $data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) === $actualValue
					) || (
						$data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)
						&& $data->offsetGet(States\Property::ACTUAL_VALUE_FIELD) === $expectedValue
					)
				) {
					$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
					$data->offsetSet(States\Property::PENDING_FIELD, false);
				}
			}
		} catch (MetadataExceptions\InvalidValue) {
			// Could be ignored
		}

		try {
			if ($state === null) {
				$result = $this->connectorPropertiesStatesManager->create(
					$property,
					$data,
				);

			} else {
				$result = $this->connectorPropertiesStatesManager->update(
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
					new Events\ConnectorPropertyStateEntityCreated(
						$property,
						$readValue,
						$getValue,
						$source ?? MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
					),
				);
			} else {
				$this->dispatcher?->dispatch(
					new Events\ConnectorPropertyStateEntityUpdated(
						$property,
						$readValue,
						$getValue,
						$source ?? MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
					),
				);
			}

			$this->logger->debug(
				$state === null ? 'Connector property state was created' : 'Connector property state was updated',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES,
					'type' => 'connector-properties-states',
					'property' => [
						'id' => $property->getId()->toString(),
						'state' => $result->toArray(),
					],
				],
			);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES,
					'type' => 'connector-properties-states',
				],
			);
		}
	}

}
