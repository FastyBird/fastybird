<?php declare(strict_types = 1);

/**
 * VirtualDevice.php
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

namespace FastyBird\Connector\Virtual\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function floatval;
use function is_numeric;

/**
 * @ORM\Entity
 */
class VirtualDevice extends DevicesEntities\Devices\Device
{

	public const TYPE = 'virtual';

	private const STATE_PROCESSING_DELAY = 120.0;

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL);
	}

	/**
	 * @return array<VirtualChannel>
	 */
	public function getChannels(): array
	{
		$channels = [];

		foreach (parent::getChannels() as $channel) {
			if ($channel instanceof VirtualChannel) {
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
