<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           28.06.23
 */

namespace FastyBird\Connector\Viera\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Viera;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Utils;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel property consumer trait
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository
 * @property-read DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository
 * @property-read DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager
 * @property-read ApplicationHelpers\Database $databaseHelper
 * @property-read Viera\Logger $logger
 */
trait ChannelProperty
{

	/**
	 * @param class-string<DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Dynamic> $type
	 * @param string|array<int, string>|array<int, string|int|float|array<int, string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 */
	private function setChannelProperty(
		string $type,
		Uuid\UuidInterface $channelId,
		string|bool|int|null $value,
		MetadataTypes\DataType $dataType,
		string $identifier,
		string|null $name = null,
		array|string|null $format = null,
		bool $settable = false,
		bool $queryable = false,
	): void
	{
		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->byChannelId($channelId);
		$findChannelPropertyQuery->byIdentifier($identifier);

		$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		if ($property !== null && $value === null && $type === DevicesEntities\Channels\Properties\Variable::class) {
			$this->databaseHelper->transaction(
				function () use ($property): void {
					$this->channelsPropertiesManager->delete($property);
				},
			);

			return;
		}

		if ($value === null && $type === DevicesEntities\Channels\Properties\Variable::class) {
			return;
		}

		if ($property !== null && !$property instanceof $type) {
			$this->databaseHelper->transaction(function () use ($property): void {
				$this->channelsPropertiesManager->delete($property);
			});

			$this->logger->warning(
				'Stored channel property was not of valid type',
				[
					'source' => MetadataTypes\Sources\Connector::VIERA,
					'type' => 'message-consumer',
					'channel' => [
						'id' => $channelId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier,
					],
				],
			);

			$property = null;
		}

		if ($property === null) {
			$channel = $this->channelsRepository->find($channelId, Viera\Entities\VieraChannel::class);

			if ($channel === null) {
				$this->logger->error(
					'Channel was not found, property could not be configured',
					[
						'source' => MetadataTypes\Sources\Connector::VIERA,
						'type' => 'message-consumer',
						'channel' => [
							'id' => $channelId->toString(),
						],
						'property' => [
							'identifier' => $identifier,
						],
					],
				);

				return;
			}

			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->create(
					Utils\ArrayHash::from(array_merge(
						[
							'entity' => $type,
							'channel' => $channel,
							'identifier' => $identifier,
							'name' => $name,
							'dataType' => $dataType,
							'format' => $format,
						],
						$type === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
					)),
				),
			);

			$this->logger->debug(
				'Channel property was created',
				[
					'source' => MetadataTypes\Sources\Connector::VIERA,
					'type' => 'message-consumer',
					'channel' => [
						'id' => $channelId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier,
					],
				],
			);

		} else {
			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->update(
					$property,
					Utils\ArrayHash::from(array_merge(
						[
							'dataType' => $dataType,
							'format' => $format,
						],
						$type === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
					)),
				),
			);

			$this->logger->debug(
				'Channel property was updated',
				[
					'source' => MetadataTypes\Sources\Connector::VIERA,
					'type' => 'message-consumer',
					'channel' => [
						'id' => $channelId->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
						'identifier' => $identifier,
					],
				],
			);
		}
	}

}
