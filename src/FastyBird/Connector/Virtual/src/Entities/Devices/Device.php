<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function floatval;
use function is_numeric;

#[ORM\MappedSuperclass]
abstract class Device extends DevicesEntities\Devices\Device
{

	public const TYPE = 'virtual-connector';

	public const STATE_PROCESSING_DELAY = 120.0;

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::VIRTUAL);
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getStateProcessingDelay(): float
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::STATE_PROCESSING_DELAY
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return self::STATE_PROCESSING_DELAY;
	}

}
