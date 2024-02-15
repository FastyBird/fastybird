<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.03.22
 */

namespace FastyBird\Connector\HomeKit\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use function assert;
use function is_int;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Device extends DevicesEntities\Devices\Device
{

	public const TYPE = 'homekit-connector';

	public function __construct(
		string $identifier,
		Entities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::HOMEKIT);
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		assert($this->connector instanceof Entities\Connectors\Connector);

		return $this->connector;
	}

	/**
	 * @return array<Entities\Channels\Channel>
	 */
	public function getChannels(): array
	{
		$channels = [];

		foreach (parent::getChannels() as $channel) {
			if ($channel instanceof Entities\Channels\Channel) {
				$channels[] = $channel;
			}
		}

		return $channels;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addChannel(DevicesEntities\Channels\Channel $channel): void
	{
		if (!$channel instanceof Entities\Channels\Channel) {
			throw new Exceptions\InvalidArgument('Provided channel type is not valid');
		}

		parent::addChannel($channel);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAccessoryCategory(): Types\AccessoryCategory
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::CATEGORY
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_int($property->getValue())
			&& Types\AccessoryCategory::isValidValue($property->getValue())
		) {
			return Types\AccessoryCategory::get($property->getValue());
		}

		return Types\AccessoryCategory::get(Types\AccessoryCategory::OTHER);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAccessoryType(): Types\AccessoryType
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::TYPE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_int($property->getValue())
			&& Types\AccessoryType::isValidValue($property->getValue())
		) {
			return Types\AccessoryType::get($property->getValue());
		}

		return Types\AccessoryType::get(Types\AccessoryType::GENERIC);
	}

	/**
	 * @return array<Entities\Channels\Channel>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findChannelsByType(Types\ServiceType $type): array
	{
		$channels = [];

		foreach (parent::getChannels() as $channel) {
			if (!$channel instanceof Entities\Channels\Channel) {
				continue;
			}

			if ($channel->getServiceType()->equals($type)) {
				$channels[] = $channel;
			}
		}

		return $channels;
	}

}
