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
 * @date           22.01.24
 */

namespace FastyBird\Module\Devices\Models\States\Async;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use React\Promise;
use Throwable;
use function assert;
use function boolval;
use function is_array;
use function React\Async\await;
use function strval;

/**
 * Useful connector dynamic property state helpers
 *
 * @extends Models\States\PropertiesManager<MetadataDocuments\DevicesModule\ConnectorDynamicProperty, null, States\ConnectorProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesManager extends Models\States\PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\States\Connectors\Async\Repository $connectorPropertyStateRepository,
		private readonly Models\States\Connectors\Async\Manager $connectorPropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		ObjectMapper\Processing\Processor $stateMapper,
	)
	{
		parent::__construct($stateMapper);
	}

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function read(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		return $this->loadValue($property, true);
	}

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function get(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		return $this->loadValue($property, false);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function write(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
	): Promise\PromiseInterface
	{
		return $this->saveValue($property, $data, true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function set(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
	): Promise\PromiseInterface
	{
		return $this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array<MetadataDocuments\DevicesModule\ConnectorDynamicProperty>|Entities\Connectors\Properties\Dynamic|array<Entities\Connectors\Properties\Dynamic> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic|array $property,
		bool $state,
	): Promise\PromiseInterface
	{
		if (is_array($property)) {
			$deferred = new Promise\Deferred();

			$promises = [];

			foreach ($property as $item) {
				$promises[] = $this->set($item, Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]));
			}

			Promise\all($promises)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->set($property, Utils\ArrayHash::from([
			States\Property::VALID_FIELD => $state,
		]));
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array<MetadataDocuments\DevicesModule\ConnectorDynamicProperty>|Entities\Connectors\Properties\Dynamic|array<Entities\Connectors\Properties\Dynamic> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setPendingState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic|array $property,
		bool $pending,
	): Promise\PromiseInterface
	{
		if (is_array($property)) {
			$deferred = new Promise\Deferred();

			$promises = [];

			foreach ($property as $item) {
				$promises[] = $pending === false ? $this->set($item, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				])) : $this->set($item, Utils\ArrayHash::from([
					States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
						DateTimeInterface::ATOM,
					),
				]));
			}

			Promise\all($promises)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $pending === false ? $this->set($property, Utils\ArrayHash::from([
			States\Property::EXPECTED_VALUE_FIELD => null,
			States\Property::PENDING_FIELD => false,
		])) : $this->set($property, Utils\ArrayHash::from([
			States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(DateTimeInterface::ATOM),
		]));
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
	): Promise\PromiseInterface
	{
		try {
			return $this->connectorPropertiesStatesManager->delete($property);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'async-connector-properties-states',
				],
			);
		}

		return Promise\resolve(false);
	}

	public function normalizePublishValue(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|null
	{
		return MetadataUtilities\Value::transformDataType(
			MetadataUtilities\Value::flattenValue($value),
			$property->getDataType(),
		);
	}

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function loadValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		bool $forReading,
	): Promise\PromiseInterface
	{
		if ($property instanceof Entities\Connectors\Properties\Property) {
			$property = $this->connectorPropertiesConfigurationRepository->find($property->getId());
			assert($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty);
		}

		try {
			$state = await($this->connectorPropertyStateRepository->find($property->getId()));

			if ($state === null) {
				return Promise\resolve(null);
			}

			$updateValues = [];

			if ($state->getActualValue() !== null) {
				try {
					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $this->convertReadValue(
						$state->getActualValue(),
						$property,
						null,
						$forReading,
					);
				} catch (MetadataExceptions\InvalidValue $ex) {
					try {
						await(
							$this->connectorPropertiesStatesManager->update(
								$property,
								$state,
								Utils\ArrayHash::from([
									States\Property::ACTUAL_VALUE_FIELD => null,
									States\Property::VALID_FIELD => false,
								]),
							),
						);

						$this->logger->error(
							'Property stored actual value was not valid',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'async-connector-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

						return $this->loadValue($property, $forReading);
					} catch (Throwable $ex) {
						return Promise\reject($ex);
					}
				}
			}

			if ($state->getExpectedValue() !== null) {
				try {
					$expectedValue = $this->convertReadValue(
						$state->getExpectedValue(),
						$property,
						null,
						$forReading,
					);

					if ($expectedValue !== null && !$property->isSettable()) {
						try {
							await(
								$this->connectorPropertiesStatesManager->update(
									$property,
									$state,
									Utils\ArrayHash::from([
										States\Property::EXPECTED_VALUE_FIELD => null,
										States\Property::PENDING_FIELD => false,
									]),
								),
							);

							$this->logger->warning(
								'Property is not settable but has stored expected value',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'async-connector-properties-states',
								],
							);

							return $this->loadValue($property, $forReading);
						} catch (Throwable $ex) {
							return Promise\reject($ex);
						}
					}

					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
				} catch (MetadataExceptions\InvalidValue $ex) {
					try {
						await(
							$this->connectorPropertiesStatesManager->update(
								$property,
								$state,
								Utils\ArrayHash::from([
									States\Property::EXPECTED_VALUE_FIELD => null,
									States\Property::PENDING_FIELD => false,
								]),
							),
						);

						$this->logger->error(
							'Property stored expected value was not valid',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'async-connector-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

						return $this->loadValue($property, $forReading);
					} catch (Throwable $ex) {
						return Promise\reject($ex);
					}
				}
			}

			if ($updateValues === []) {
				return Promise\resolve($state);
			}

			return Promise\resolve($this->updateState($state, $state::class, $updateValues));
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'async-connector-properties-states',
				],
			);
		} catch (Throwable $ex) {
			return Promise\reject($ex);
		}

		return Promise\resolve(null);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function saveValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): Promise\PromiseInterface
	{
		if ($property instanceof Entities\Connectors\Properties\Property) {
			$property = $this->connectorPropertiesConfigurationRepository->find($property->getId());
			assert($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty);
		}

		try {
			$state = await($this->connectorPropertyStateRepository->find($property->getId()));
		} catch (Exceptions\NotImplemented) {
			$state = null;
		} catch (Throwable $ex) {
			return Promise\reject($ex);
		}

		/**
		 * IMPORTANT: ACTUAL VALUE field is meant to be used only by connectors for saving device actual value
		 */
		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			if ($forWriting === true) {
				throw new Exceptions\InvalidArgument(
					'Setting property actual value could be done only by "setValue" method',
				);
			}

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

					if ($data->offsetExists(States\Property::VALID_FIELD)) {
						$data->offsetSet(
							States\Property::VALID_FIELD,
							boolval($data->offsetGet(States\Property::VALID_FIELD)),
						);
					} else {
						$data->offsetSet(States\Property::VALID_FIELD, true);
					}
				}
			} catch (MetadataExceptions\InvalidValue $ex) {
				$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'async-connector-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		/**
		 * IMPORTANT: EXPECTED VALUE field is meant to be used mainly by user interface for saving value which should
		 * be then written into device
		 */
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

					if ($expectedValue !== null && (!$property->isSettable())) {
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
							'type' => 'async-connector-properties-states',
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
			return Promise\resolve(true);
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

		$deferred = new Promise\Deferred();

		$result = $state === null ? $this->connectorPropertiesStatesManager->create(
			$property,
			$data,
		) : $this->connectorPropertiesStatesManager->update(
			$property,
			$state,
			$data,
		);

		$result
			->then(function (States\ConnectorProperty $result) use ($state, $property): void {
				$this->logger->debug(
					$state === null ? 'Connector property state was created' : 'Connector property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'async-connector-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $result->toArray(),
						],
					],
				);
			})
			->catch(function (Throwable $ex) use ($deferred): void {
				if ($ex instanceof Exceptions\NotImplemented) {
					$this->logger->warning(
						'Connectors states manager is not configured. State could not be saved',
						[
							'source' => MetadataTypes\ModuleSource::DEVICES,
							'type' => 'async-connector-properties-states',
						],
					);
				}

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
