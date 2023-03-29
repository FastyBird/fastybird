<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           23.08.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Psr\Log;
use function is_array;

/**
 * Useful connector dynamic property state helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesStates
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Models\States\ConnectorPropertiesRepository $connectorPropertyStateRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getValue(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): States\ConnectorProperty|null
	{
		try {
			$state = $this->connectorPropertyStateRepository->findOne($property);

			if ($state !== null) {
				if ($state->getActualValue() !== null) {
					$state->setActualValue(
						ValueHelper::normalizeValue(
							$property->getDataType(),
							$state->getActualValue(),
							$property->getFormat(),
							$property->getInvalid(),
						),
					);
				}

				if ($state->getExpectedValue() !== null) {
					$state->setActualValue(
						ValueHelper::normalizeValue(
							$property->getDataType(),
							$state->getExpectedValue(),
							$property->getFormat(),
							$property->getInvalid(),
						),
					);
				}
			}

			return $state;
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'connector-properties-states',
				],
			);
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValue(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
	): void
	{
		try {
			$propertyState = $this->connectorPropertyStateRepository->findOne($property);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'connector-properties-states',
				],
			);

			return;
		}

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_KEY)) {
			$data->offsetSet(
				States\Property::ACTUAL_VALUE_KEY,
				ValueHelper::flattenValue(
					ValueHelper::normalizeValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_KEY),
						$property->getFormat(),
						$property->getInvalid(),
					),
				),
			);
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_KEY)) {
			$data->offsetSet(
				States\Property::EXPECTED_VALUE_KEY,
				ValueHelper::flattenValue(
					ValueHelper::normalizeValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_KEY),
						$property->getFormat(),
						$property->getInvalid(),
					),
				),
			);
		}

		try {
			// In case synchronization failed...
			if ($propertyState === null) {
				// ...create state in storage
				$propertyState = $this->connectorPropertiesStatesManager->create(
					$property,
					$data,
				);

				$this->logger->debug(
					'Connector property state was created',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $propertyState->toArray(),
						],
					],
				);
			} else {
				$propertyState = $this->connectorPropertiesStatesManager->update(
					$property,
					$propertyState,
					$data,
				);

				$this->logger->debug(
					'Connector property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $propertyState->toArray(),
						],
					],
				);
			}
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'connector-properties-states',
				],
			);
		}
	}

	/**
	 * @param MetadataEntities\DevicesModule\ConnectorDynamicProperty|array<MetadataEntities\DevicesModule\ConnectorDynamicProperty>|Entities\Connectors\Properties\Dynamic|array<Entities\Connectors\Properties\Dynamic> $property
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValidState(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->setValue($item, Utils\ArrayHash::from([
					States\Property::VALID_KEY => $state,
				]));
			}
		} else {
			$this->setValue($property, Utils\ArrayHash::from([
				States\Property::VALID_KEY => $state,
			]));
		}
	}

}
