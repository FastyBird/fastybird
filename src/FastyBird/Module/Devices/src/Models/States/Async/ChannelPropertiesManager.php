<?php declare(strict_types = 1);

/**
 * ChannelPropertiesStates.php
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
use FastyBird\Module\Devices\Utilities;
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
 * Useful channel dynamic property state helpers
 *
 * @extends Models\States\PropertiesManager<MetadataDocuments\DevicesModule\ChannelDynamicProperty, MetadataDocuments\DevicesModule\ChannelMappedProperty | null, States\ChannelProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertiesManager extends Models\States\PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\Channels\Async\Repository $channelPropertyStateRepository,
		private readonly Models\States\Channels\Async\Manager $channelPropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		ObjectMapper\Processing\Processor $stateMapper,
	)
	{
		parent::__construct($stateMapper);
	}

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function read(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
	): Promise\PromiseInterface
	{
		return $this->loadValue($property, true);
	}

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function get(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): Promise\PromiseInterface
	{
		return $this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ChannelDynamicProperty|array<MetadataDocuments\DevicesModule\ChannelDynamicProperty>|Entities\Channels\Properties\Dynamic|array<Entities\Channels\Properties\Dynamic> $property
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic|array $property,
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
	 * @param MetadataDocuments\DevicesModule\ChannelDynamicProperty|array<MetadataDocuments\DevicesModule\ChannelDynamicProperty>|Entities\Channels\Properties\Dynamic|array<Entities\Channels\Properties\Dynamic> $property
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic|array $property,
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
	): Promise\PromiseInterface
	{
		try {
			return $this->channelPropertiesStatesManager->delete($property);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states manager is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'async-channel-properties-states',
				],
			);
		}

		return Promise\resolve(false);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function normalizePublishValue(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|null
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		if ($mappedProperty !== null) {
			if (
				!Utilities\Value::compareDataTypes(
					$mappedProperty->getDataType(),
					$property->getDataType(),
				)
			) {
				throw new Exceptions\InvalidState(
					'Mapped property data type is not compatible with dynamic property data type',
				);
			}
		}

		return MetadataUtilities\Value::transformDataType(
			MetadataUtilities\Value::flattenValue($value),
			$mappedProperty?->getDataType() ?? $property->getDataType(),
		);
	}

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function loadValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		bool $forReading,
	): Promise\PromiseInterface
	{
		if ($property instanceof Entities\Channels\Properties\Property) {
			$property = $this->channelPropertiesConfigurationRepository->find($property->getId());
			assert(
				$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty,
			);
		}

		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				return Promise\reject(new Exceptions\InvalidState('Mapped property parent could not be loaded'));
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = await($this->channelPropertyStateRepository->find($property->getId()));

			if ($state === null) {
				return Promise\resolve(null);
			}

			$updateValues = [];

			if ($mappedProperty !== null) {
				$updateValues['id'] = $mappedProperty->getId();
			}

			if ($state->getActualValue() !== null) {
				try {
					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $this->convertReadValue(
						$state->getActualValue(),
						$property,
						$mappedProperty,
						$forReading,
					);
				} catch (MetadataExceptions\InvalidValue $ex) {
					if ($mappedProperty !== null) {
						$updateValues[States\Property::ACTUAL_VALUE_FIELD] = null;
						$updateValues[States\Property::VALID_FIELD] = false;

						$this->logger->error(
							'Property stored actual value could not be converted to mapped property',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'async-channel-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

					} else {
						try {
							await(
								$this->channelPropertiesStatesManager->update(
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
									'type' => 'async-channel-properties-states',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);

							return $this->loadValue($property, $forReading);
						} catch (Throwable $ex) {
							return Promise\reject($ex);
						}
					}
				}
			}

			if ($state->getExpectedValue() !== null) {
				try {
					$expectedValue = $this->convertReadValue(
						$state->getExpectedValue(),
						$property,
						$mappedProperty,
						$forReading,
					);

					if ($expectedValue !== null && !$property->isSettable()) {
						try {
							await(
								$this->channelPropertiesStatesManager->update(
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
									'type' => 'async-channel-properties-states',
								],
							);

							return $this->loadValue($mappedProperty ?? $property, $forReading);
						} catch (Throwable $ex) {
							return Promise\reject($ex);
						}
					}

					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
				} catch (MetadataExceptions\InvalidValue $ex) {
					if ($mappedProperty !== null) {
						$updateValues[States\Property::EXPECTED_VALUE_FIELD] = null;
						$updateValues[States\Property::PENDING_FIELD] = false;

						$this->logger->error(
							'Property stored actual value could not be converted to mapped property',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'async-channel-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

					} else {
						try {
							await(
								$this->channelPropertiesStatesManager->update(
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
									'type' => 'async-channel-properties-states',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);

							return $this->loadValue($property, $forReading);
						} catch (Throwable $ex) {
							return Promise\reject($ex);
						}
					}
				}
			}

			if ($updateValues === []) {
				return Promise\resolve($state);
			}

			return Promise\resolve($this->updateState($state, $state::class, $updateValues));
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'async-channel-properties-states',
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): Promise\PromiseInterface
	{
		if ($property instanceof Entities\Channels\Properties\Property) {
			$property = $this->channelPropertiesConfigurationRepository->find($property->getId());
			assert(
				$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty,
			);
		}

		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				return Promise\reject(new Exceptions\InvalidState('Mapped property parent could not be loaded'));
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = await($this->channelPropertyStateRepository->find($property->getId()));
		} catch (Exceptions\NotImplemented) {
			$state = null;
		} catch (Throwable $ex) {
			return Promise\reject($ex);
		}

		/**
		 * IMPORTANT: ACTUAL VALUE field is meant to be used only by connectors for saving device actual value
		 */
		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			if ($mappedProperty !== null) {
				throw new Exceptions\InvalidArgument(
					'Setting property actual value is not allowed for mapped properties',
				);
			}

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
						'type' => 'async-channel-properties-states',
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
							'type' => 'async-channel-properties-states',
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

		$result = $state === null ? $this->channelPropertiesStatesManager->create(
			$property,
			$data,
		) : $this->channelPropertiesStatesManager->update(
			$property,
			$state,
			$data,
		);

		$result
			->then(function (States\ChannelProperty $result) use ($state, $property): void {
				$this->logger->debug(
					$state === null ? 'Channel property state was created' : 'Channel property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'async-channel-properties-states',
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
						'Channels states manager is not configured. State could not be saved',
						[
							'source' => MetadataTypes\ModuleSource::DEVICES,
							'type' => 'async-channel-properties-states',
						],
					);
				}

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
