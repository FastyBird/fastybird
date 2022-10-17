<?php declare(strict_types = 1);

/**
 * ConnectorConnectionStateManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     DynamicProperties
 * @since          0.73.0
 *
 * @date           19.07.22
 */

namespace FastyBird\DevicesModule\Models\States;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Types as MetadataTypes;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Connector connection states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     DynamicProperties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorConnectionStateManager
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Models\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\DataStorage\ConnectorPropertiesRepository $repository,
		private readonly Models\Connectors\Properties\PropertiesManager $manager,
		private readonly Models\States\ConnectorPropertiesRepository $statesRepository,
		private readonly Models\States\ConnectorPropertiesManager $statesManager,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setState(
		Entities\Connectors\Connector|MetadataEntities\DevicesModule\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$stateProperty = $this->repository->findByIdentifier(
			$connector->getId(),
			MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
		);

		if ($stateProperty === null) {
			if (!$connector instanceof Entities\Connectors\Connector) {
				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byId($connector->getId());

				$connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

				if ($connector === null) {
					throw new Exceptions\InvalidState('Connector could not be loaded');
				}
			}

			$stateProperty = $this->manager->create(Utils\ArrayHash::from([
				'connector' => $connector,
				'entity' => Entities\Connectors\Properties\Dynamic::class,
				'identifier' => MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::STATE_RUNNING,
					MetadataTypes\ConnectionState::STATE_STOPPED,
					MetadataTypes\ConnectionState::STATE_UNKNOWN,
					MetadataTypes\ConnectionState::STATE_SLEEPING,
					MetadataTypes\ConnectionState::STATE_ALERT,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}

		if (
			$stateProperty instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $stateProperty instanceof Entities\Connectors\Properties\Dynamic
		) {
			try {
				$statePropertyState = $this->statesRepository->findOne($stateProperty);

			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'DynamicProperties repository is not configured. State could not be fetched',
					[
						'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
						'type' => 'connector-connection-state-manager',
					],
				);

				MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);

				return false;
			}

			if ($statePropertyState === null) {
				try {
					$this->statesManager->create($stateProperty, Utils\ArrayHash::from([
						'actualValue' => $state->getValue(),
						'expectedValue' => null,
						'pending' => false,
						'valid' => true,
					]));

					return true;
				} catch (Exceptions\NotImplemented) {
					$this->logger->warning(
						'DynamicProperties manager is not configured. State could not be saved',
						[
							'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
							'type' => 'connector-connection-state-manager',
						],
					);
				}
			} else {
				try {
					$this->statesManager->update(
						$stateProperty,
						$statePropertyState,
						Utils\ArrayHash::from([
							'actualValue' => $state->getValue(),
							'expectedValue' => null,
							'pending' => false,
							'valid' => true,
						]),
					);

					return true;
				} catch (Exceptions\NotImplemented) {
					$this->logger->warning(
						'DynamicProperties manager is not configured. State could not be saved',
						[
							'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
							'type' => 'connector-connection-state-manager',
						],
					);
				}
			}
		}

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getState(
		Entities\Connectors\Connector|MetadataEntities\DevicesModule\Connector $connector,
	): MetadataTypes\ConnectionState
	{
		$stateProperty = $this->repository->findByIdentifier(
			$connector->getId(),
			MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
		);

		if (
			$stateProperty instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			&& $stateProperty->getActualValue() !== null
			&& MetadataTypes\ConnectionState::isValidValue($stateProperty->getActualValue())
		) {
			return MetadataTypes\ConnectionState::get($stateProperty->getActualValue());
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);
	}

}
